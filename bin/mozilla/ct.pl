#=====================================================================
# LX-Office ERP
# Copyright (C) 2004
# Based on SQL-Ledger Version 2.1.9
# Web http://www.lx-office.org
#
#=====================================================================
# SQL-Ledger Accounting
# Copyright (c) 1998-2002
#
#  Author: Dieter Simader
#   Email: dsimader@sql-ledger.org
#     Web: http://www.sql-ledger.org
#
#  Contributors: Reed White <alta@alta-research.com>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
#======================================================================
#
# customer/vendor module
#
#======================================================================

# $locale->text('Customers')
# $locale->text('Vendors')
# $locale->text('Add Customer')
# $locale->text('Add Vendor')
# $locale->text('Edit Customer')
# $locale->text('Edit Vendor')
# $locale->text('Customer saved!')
# $locale->text('Vendor saved!')
# $locale->text('Customer deleted!')
# $locale->text('Cannot delete customer!')
# $locale->text('Vendor deleted!')
# $locale->text('Cannot delete vendor!')

use POSIX qw(strftime);

use SL::CT;
use SL::CVar;
use SL::Request qw(flatten);
use SL::DB::Business;
use SL::DB::Default;
use SL::DB::DeliveryTerm;
use SL::Helper::Flash;
use SL::ReportGenerator;
use SL::MoreCommon qw(uri_encode);

require "bin/mozilla/common.pl";
require "bin/mozilla/reportgenerator.pl";

use strict;
1;

# end of main

sub search {
  $main::lxdebug->enter_sub();

  $main::auth->assert('customer_vendor_edit');

  my $form     = $main::form;
  my $locale   = $main::locale;

  $form->{IS_CUSTOMER} = $form->{db} eq 'customer';

  $form->get_lists("business_types" => "ALL_BUSINESS_TYPES");
  $form->{SHOW_BUSINESS_TYPES} = scalar @{ $form->{ALL_BUSINESS_TYPES} } > 0;

  $form->{CUSTOM_VARIABLES}                  = CVar->get_configs('module' => 'CT');
  ($form->{CUSTOM_VARIABLES_FILTER_CODE},
   $form->{CUSTOM_VARIABLES_INCLUSION_CODE}) = CVar->render_search_options('variables'      => $form->{CUSTOM_VARIABLES},
                                                                           'include_prefix' => 'l_',
                                                                           'include_value'  => 'Y');

  $form->{title}    = $form->{IS_CUSTOMER} ? $locale->text('Customers') : $locale->text('Vendors');

  $form->header();
  print $form->parse_html_template('ct/search');

  $main::lxdebug->leave_sub();
}

sub search_contact {
  $::lxdebug->enter_sub;
  $::auth->assert('customer_vendor_edit');

  $::form->{CUSTOM_VARIABLES}                  = CVar->get_configs('module' => 'Contacts');
  ($::form->{CUSTOM_VARIABLES_FILTER_CODE},
   $::form->{CUSTOM_VARIABLES_INCLUSION_CODE}) = CVar->render_search_options('variables'    => $::form->{CUSTOM_VARIABLES},
                                                                           'include_prefix' => 'l.',
                                                                           'filter_prefix'  => 'filter.',
                                                                           'include_value'  => 'Y');

  $::form->{title} = $::locale->text('Search contacts');
  $::form->header;
  print $::form->parse_html_template('ct/search_contact');

  $::lxdebug->leave_sub;
}

sub list_names {
  $main::lxdebug->enter_sub();

  $main::auth->assert('customer_vendor_edit');

  my $form     = $main::form;
  my %myconfig = %main::myconfig;
  my $locale   = $main::locale;

  $form->{IS_CUSTOMER} = $form->{db} eq 'customer';

  report_generator_set_default_sort('name', 1);

  CT->search(\%myconfig, \%$form);

  my $cvar_configs = CVar->get_configs('module' => 'CT');

  my @options;
  if ($form->{status} eq 'all') {
    push @options, $locale->text('All');
  } elsif ($form->{status} eq 'orphaned') {
    push @options, $locale->text('Orphaned');
  }

  push @options, $locale->text('Name') . " : $form->{name}"                                    if $form->{name};
  push @options, $locale->text('Contact') . " : $form->{contact}"                              if $form->{contact};
  push @options, $locale->text('Number') . qq| : $form->{"$form->{db}number"}|                 if $form->{"$form->{db}number"};
  push @options, $locale->text('E-mail') . " : $form->{email}"                                 if $form->{email};
  push @options, $locale->text('Contact person (surname)')           . " : $form->{cp_name}"   if $form->{cp_name};
  push @options, $locale->text('Billing/shipping address (city)')    . " : $form->{addr_city}" if $form->{addr_city};
  push @options, $locale->text('Billing/shipping address (zipcode)') . " : $form->{zipcode}"   if $form->{addr_zipcode};
  push @options, $locale->text('Billing/shipping address (street)')  . " : $form->{street}"    if $form->{addr_street};
  push @options, $locale->text('Billing/shipping address (country)') . " : $form->{country}"   if $form->{addr_country};

  if ($form->{business_id}) {
    my $business = SL::DB::Manager::Business->find_by(id => $form->{business_id});
    if ($business) {
      my $label = $form->{IS_CUSTOMER} ? $::locale->text('Customer type') : $::locale->text('Vendor type');
      push @options, $label . " : " . $business->description;
    }
  }

  my @columns = (
    'id',        'name',      "$form->{db}number",   'contact',   'phone',    'discount',
    'fax',       'email',     'taxnumber',           'street',    'zipcode' , 'city',
    'business',  'invnumber', 'ordnumber',           'quonumber', 'salesman', 'country'
  );

  my @includeable_custom_variables = grep { $_->{includeable} } @{ $cvar_configs };
  my @searchable_custom_variables  = grep { $_->{searchable} }  @{ $cvar_configs };
  my %column_defs_cvars            = map { +"cvar_$_->{name}" => { 'text' => $_->{description} } } @includeable_custom_variables;

  push @columns, map { "cvar_$_->{name}" } @includeable_custom_variables;

  my %column_defs = (
    'id'                => { 'text' => $locale->text('ID'), },
    "$form->{db}number" => { 'text' => $locale->text('Number'), },
    'name'              => { 'text' => $form->{IS_CUSTOMER} ? $::locale->text('Customer Name') : $::locale->text('Vendor Name'), },
    'contact'           => { 'text' => $locale->text('Contact'), },
    'phone'             => { 'text' => $locale->text('Phone'), },
    'fax'               => { 'text' => $locale->text('Fax'), },
    'email'             => { 'text' => $locale->text('E-mail'), },
    'cc'                => { 'text' => $locale->text('Cc'), },
    'taxnumber'         => { 'text' => $locale->text('Tax Number'), },
    'business'          => { 'text' => $locale->text('Type of Business'), },
    'invnumber'         => { 'text' => $locale->text('Invoice'), },
    'ordnumber'         => { 'text' => $form->{IS_CUSTOMER} ? $locale->text('Sales Order') : $locale->text('Purchase Order'), },
    'quonumber'         => { 'text' => $form->{IS_CUSTOMER} ? $locale->text('Quotation')   : $locale->text('Request for Quotation'), },
    'street'            => { 'text' => $locale->text('Street'), },
    'zipcode'           => { 'text' => $locale->text('Zipcode'), },
    'city'              => { 'text' => $locale->text('City'), },
    'country'           => { 'text' => $locale->text('Country'), },
    'salesman'          => { 'text' => $locale->text('Salesman'), },
    'discount'          => { 'text' => $locale->text('Discount'), },
    %column_defs_cvars,
  );

  map { $column_defs{$_}->{visible} = $form->{"l_$_"} eq 'Y' } @columns;

  my @hidden_variables  = ( qw(
      db status obsolete name contact email cp_name addr_street addr_zipcode
      addr_city addr_country business_id
    ), "$form->{db}number",
    map({ "cvar_$_->{name}" } @searchable_custom_variables),
    map({'cvar_'. $_->{name} .'_qtyop'} grep({$_->{type} eq 'number'} @searchable_custom_variables)),
    map({ "l_$_" } @columns),
  );

  my @hidden_nondefault = grep({ $form->{$_} } @hidden_variables);
  my $callback          = build_std_url('action=list_names', grep { $form->{$_} } @hidden_nondefault);
  $form->{callback}     = "$callback&sort=" . E($form->{sort}) . "&sortdir=" . E($form->{sortdir});

  foreach (@columns) {
    my $sortdir              = $form->{sort} eq $_ ? 1 - $form->{sortdir} : $form->{sortdir};
    $column_defs{$_}->{link} = "${callback}&sort=${_}&sortdir=${sortdir}";
  }

  my ($ordertype, $quotationtype, $attachment_basename);
  if ($form->{IS_CUSTOMER}) {
    $form->{title}       = $locale->text('Customers');
    $ordertype           = 'sales_order';
    $quotationtype       = 'sales_quotation';
    $attachment_basename = $locale->text('customer_list');

  } else {
    $form->{title}       = $locale->text('Vendors');
    $ordertype           = 'purchase_order';
    $quotationtype       = 'request_quotation';
    $attachment_basename = $locale->text('vendor_list');
  }

  my $report = SL::ReportGenerator->new(\%myconfig, $form);

  $report->set_options('top_info_text'         => join("\n", @options),
                       'raw_bottom_info_text'  => $form->parse_html_template('ct/list_names_bottom'),
                       'output_format'         => 'HTML',
                       'title'                 => $form->{title},
                       'attachment_basename'   => $attachment_basename . strftime('_%Y%m%d', localtime time),
    );
  $report->set_options_from_form();
  $locale->set_numberformat_wo_thousands_separator(\%myconfig) if lc($report->{options}->{output_format}) eq 'csv';

  $report->set_columns(%column_defs);
  $report->set_column_order(@columns);

  $report->set_export_options('list_names', @hidden_variables, qw(sort sortdir));

  $report->set_sort_indicator($form->{sort}, $form->{sortdir});

  CVar->add_custom_variables_to_report('module'         => 'CT',
                                       'trans_id_field' => 'id',
                                       'configs'        => $cvar_configs,
                                       'column_defs'    => \%column_defs,
                                       'data'           => $form->{CT});

  my $previous_id;

  foreach my $ref (@{ $form->{CT} }) {
    my $row = { map { $_ => { 'data' => '' } } @columns };

    if ($ref->{id} ne $previous_id) {
      $previous_id = $ref->{id};
      $ref->{discount} = $form->format_amount(\%myconfig, $ref->{discount} * 100.0, 2);
      map { $row->{$_}->{data} = $ref->{$_} } @columns;

      $row->{name}->{link}  = build_std_url('script=controller.pl', 'action=CustomerVendor/edit', 'id=' . E($ref->{id}), 'callback', @hidden_nondefault);
      $row->{email}->{link} = 'mailto:' . E($ref->{email});
    }

    my $base_url              = build_std_url("script=$ref->{module}.pl", 'action=edit', 'id=' . E($ref->{invid}), 'callback', @hidden_nondefault);
    $row->{invnumber}->{link} = $base_url;
    $row->{ordnumber}->{link} = $base_url . "&type=${ordertype}";
    $row->{quonumber}->{link} = $base_url . "&type=${quotationtype}";
    my $column                = $ref->{formtype} eq 'invoice' ? 'invnumber' : $ref->{formtype} eq 'order' ? 'ordnumber' : 'quonumber';
    $row->{$column}->{data}   = $ref->{$column};

    $report->add_data($row);
  }

  $report->generate_with_headers();

  $main::lxdebug->leave_sub();
}

sub list_contacts {
  $::lxdebug->enter_sub;
  $::auth->assert('customer_vendor_edit');

  $::form->{sortdir} = 1 unless defined $::form->{sortdir};

  my @contacts     = CT->search_contacts(
    search_term => $::form->{search_term},
    filter      => $::form->{filter},
  );

  my $cvar_configs = CVar->get_configs('module' => 'Contacts');

  my @columns      = qw(
    cp_id vcname vcnumber cp_name cp_givenname cp_street cp_zipcode cp_city cp_phone1 cp_phone2 cp_privatphone
    cp_mobile1 cp_mobile2 cp_fax cp_email cp_privatemail cp_abteilung cp_position cp_birthday cp_gender
  );

  my @includeable_custom_variables = grep { $_->{includeable} } @{ $cvar_configs };
  my @searchable_custom_variables  = grep { $_->{searchable} }  @{ $cvar_configs };
  my %column_defs_cvars            = map { +"cvar_$_->{name}" => { 'text' => $_->{description} } } @includeable_custom_variables;

  push @columns, map { "cvar_$_->{name}" } @includeable_custom_variables;

  my @visible_columns;
  if ($::form->{l}) {
    @visible_columns = grep { $::form->{l}{$_} } @columns;
    push @visible_columns, qw(cp_phone1 cp_phone2)   if $::form->{l}{cp_phone};
    push @visible_columns, qw(cp_mobile1 cp_mobile2) if $::form->{l}{cp_mobile};
  } else {
   @visible_columns = qw(vcname vcnumber cp_name cp_givenname cp_phone1 cp_phone2 cp_mobile1 cp_email);
  }

  my %column_defs  = (
    'cp_id'        => { 'text' => $::locale->text('ID'), },
    'vcname'       => { 'text' => $::locale->text('Customer/Vendor'), },
    'vcnumber'     => { 'text' => $::locale->text('Customer/Vendor Number'), },
    'cp_name'      => { 'text' => $::locale->text('Name'), },
    'cp_givenname' => { 'text' => $::locale->text('Given Name'), },
    'cp_street'    => { 'text' => $::locale->text('Street'), },
    'cp_zipcode'   => { 'text' => $::locale->text('Zipcode'), },
    'cp_city'      => { 'text' => $::locale->text('City'), },
    'cp_phone1'    => { 'text' => $::locale->text('Phone1'), },
    'cp_phone2'    => { 'text' => $::locale->text('Phone2'), },
    'cp_mobile1'   => { 'text' => $::locale->text('Mobile1'), },
    'cp_mobile2'   => { 'text' => $::locale->text('Mobile2'), },
    'cp_email'     => { 'text' => $::locale->text('E-mail'), },
    'cp_abteilung' => { 'text' => $::locale->text('Department'), },
    'cp_position'  => { 'text' => $::locale->text('Function/position'), },
    'cp_birthday'  => { 'text' => $::locale->text('Birthday'), },
    'cp_gender'    => { 'text' => $::locale->text('Gender'), },
    'cp_fax'       => { 'text' => $::locale->text('Fax'), },
    'cp_privatphone' => { 'text' => $::locale->text('Private Phone') },
    'cp_privatemail' => { 'text' => $::locale->text('Private E-mail') },
    %column_defs_cvars,
  );

  map { $column_defs{$_}->{visible} = 1 } @visible_columns;

  my @hidden_variables  = (qw(search_term filter l));
  my $hide_vars         = { map { $_ => $::form->{$_} } @hidden_variables };
  my @hidden_nondefault = grep({ $::form->{$_} } @hidden_variables);
  my $callback          = build_std_url('action=list_contacts', join '&', map { E($_->[0]) . '=' . E($_->[1]) } @{ flatten($hide_vars) });
  $::form->{callback}     = "$callback&sort=" . E($::form->{sort});

  map { $column_defs{$_}->{link} = "${callback}&sort=${_}&sortdir=" . ($::form->{sort} eq $_ ? 1 - $::form->{sortdir} : $::form->{sortdir}) } @columns;

  $::form->{title} = $::locale->text('Contacts');

  my $report     = SL::ReportGenerator->new(\%::myconfig, $::form);

  my @options;
  push @options, $::locale->text('Search term') . ': ' . $::form->{search_term} if $::form->{search_term};
  for (qw(cp_name cp_givenname cp_title cp_email cp_abteilung cp_project)) {
    push @options, $column_defs{$_}{text} . ': ' . $::form->{filter}{$_} if $::form->{filter}{$_};
  }
  if ($::form->{filter}{status}) {
    push @options, $::locale->text('Status') . ': ' . (
      $::form->{filter}{status} =~ /active/   ? $::locale->text('Active')   :
      $::form->{filter}{status} =~ /orphaned/ ? $::locale->text('Orphaned') :
      $::form->{filter}{status} =~ /all/      ? $::locale->text('All')      : ''
    );
  }


  $report->set_options('top_info_text'       => join("\n", @options),
                       'output_format'       => 'HTML',
                       'title'               => $::form->{title},
                       'attachment_basename' => $::locale->text('contact_list') . strftime('_%Y%m%d', localtime time),
    );
  $report->set_options_from_form;

  $report->set_columns(%column_defs);
  $report->set_column_order(@columns);

  $report->set_export_options('list_contacts', @hidden_variables);

  $report->set_sort_indicator($::form->{sort}, $::form->{sortdir});

  CVar->add_custom_variables_to_report('module'         => 'Contacts',
                                       'trans_id_field' => 'cp_id',
                                       'configs'        => $cvar_configs,
                                       'column_defs'    => \%column_defs,
                                       'data'           => \@contacts);


  foreach my $ref (@contacts) {
    my $row = { map { $_ => { 'data' => $ref->{$_} } } @columns };

    $row->{vcname}->{link}   = build_std_url('script=controller.pl', 'action=CustomerVendor/edit', 'id=' . E($ref->{vcid}), 'db=' . E($ref->{db}), 'callback', @hidden_nondefault);
    $row->{vcnumber}->{link} = $row->{vcname}->{link};

    for (qw(cp_email cp_privatemail)) {
      $row->{$_}->{link} = 'mailto:' . E($ref->{$_}) if $ref->{$_};
    }

    $report->add_data($row);
  }

  $report->generate_with_headers;

  $::lxdebug->leave_sub;
}

sub continue { call_sub($main::form->{nextsub}); }
