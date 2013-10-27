<?php
/*
 *   Copyright (C) 2003-2004 Solarix
 *
 */
 
// ----- Initialization -----
//
require( 'auth.php' );

OpenLibrary( 'xencore.library' );
OpenLibrary( 'xenbilling.library' );
OpenLibrary( 'hui.library' );
OpenLibrary( 'locale.library' );

$gXen_core = new XenCore(
    $gEnv['root']['db'],
    $gEnv['site']['db']
    );

$gLocale = new Locale(
    'xenbilling_site_main',
    $gEnv['user']['locale']['language']
    );

$gHui = new Hui( $gEnv['root']['db'] );
$gHui->LoadWidget( 'xml' );
$gHui->LoadWidget( 'amppage' );
$gHui->LoadWidget( 'amptoolbar' );
$gHui->LoadWidget( 'table' );

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->GetStr( 'invoices.title' );
$gCore_toolbars = $gXen_core->GetMainToolBar();
$gToolbars['invoices'] = array(
    'invoices' => array(
        'label' => $gLocale->GetStr( 'invoices.toolbar' ),
        'themeimage' => 'view_icon',
        'horiz' => 'true',
        'action' => build_events_call_string( 'xenbilling.php', array( array(
            'main',
            'default',
            '' ) ) )
        ),
    'newinvoice' => array(
        'label' => $gLocale->GetStr( 'newinvoice.toolbar' ),
        'themeimage' => 'filenew',
        'horiz' => 'true',
        'action' => build_events_call_string( 'xenbilling.php', array( array(
            'main',
            'newinvoice',
            '' ) ) )
        )
    );

$gToolbars['prefs'] = array(
    'prefs' => array(
        'label' => $gLocale->GetStr( 'preferences.toolbar' ),
        'themeimage' => 'configure',
        'horiz' => 'true',
        'action' => build_events_call_string( 'xenbillingprefs.php', array( array(
            'main',
            'default',
            '' ) ) )
        )
    );

// ----- Action dispatcher -----
//
$gAction_disp = new HuiDispatcher( 'action' );

$gAction_disp->AddEvent(
    'newinvoice',
    'action_newinvoice'
    );
function action_newinvoice( $eventData )
{
    global $gEnv, $gLocale, $gPage_status;

    $xen_project = new XenInvoice(
        $gEnv['root']['db'],
        $gEnv['site']['db']
        );

    if ( $eventData['customerid'] )
    {
        $customer = new XenDirectoryCompany(
            $GLOBALS['gEnv']['root']['db'],
            $GLOBALS['gEnv']['site']['db'],
            $eventData['customerid']
            );

        $customer_data = $customer->GetItem();

        $eventData['accountmanager'] = $customer_data['accountmanager'];
    }

    if ( $eventData['paymentid'] )
    {
        $tmp_payment = new XenBilling_Payment(
            $eventData['paymentid']
            );

            OpenLibrary('locale.library');
            $country = new LocaleCountry( $GLOBALS['gEnv']['user']['locale']['country'] );

            $date_array = $country->GetDateArrayFromShortDateStamp( $eventData['emissiondate'] );
            $emission_date_tstamp = mktime(
                0,
                0,
                0,
                $date_array['mon'],
                $date_array['mday'],
                $date_array['year']
                );

            $due_date_tstamp = $emission_date_tstamp + ( 3600 * 24 * $tmp_payment->GetDays() );

            if ( $tmp_payment->GetMonthEnd() )
            {
                $day = date( 'j', $due_date_tstamp );
                $lday = date( 't', $due_date_tstamp );
                $days = $lday - $day;
                $due_date_tstamp += 3600 * 24 * $days;
            }

            $due_date = $country->SafeFormatTimestamp( $due_date_tstamp );

            $eventData['duedate'] = $country->FormatShortArrayDate(
                $country->GetDateArrayFromSafeTimestamp(
                    $due_date
                    ) );
    }
    else
    {
        $eventData['duedate'] = $eventData['emissiondate'];
    }

    if ( $xen_project->Create(
        $eventData,
        $gEnv['user']['serial']
        ) )
    {
        $GLOBALS['gEnv']['runtime']['xen-billing']['newinvoiceid'] = $xen_project->mItemId;
        $gPage_status = $gLocale->GetStr( 'invoice_added.status' );
    }
    else $gPage_status = $gLocale->GetStr( 'invoice_not_added.status' );
}

$gAction_disp->AddEvent(
    'editinvoice',
    'action_editinvoice'
    );
function action_editinvoice( $eventData )
{
    global $gEnv, $gLocale, $gPage_status;

    $xen_invoice = new XenInvoice(
        $gEnv['root']['db'],
        $gEnv['site']['db'],
        $eventData['id']
        );

    if ( $xen_invoice->Edit(
        $eventData,
        $gEnv['user']['serial']
        ) ) $gPage_status = $gLocale->GetStr( 'invoice_updated.status' );
    else $gPage_status = $gLocale->GetStr( 'invoice_not_updated.status' );
}

$gAction_disp->AddEvent(
    'removeinvoice',
    'action_removeinvoice'
    );
function action_removeinvoice( $eventData )
{
    global $gEnv, $gLocale, $gPage_status;

    $xen_invoice = new XenInvoice(
        $gEnv['root']['db'],
        $gEnv['site']['db'],
        $eventData['id']
        );

    if ( $xen_invoice->Remove(
        $gEnv['user']['serial']
        ) ) $gPage_status = $gLocale->GetStr( 'invoice_removed.status' );
    else $gPage_status = $gLocale->GetStr( 'invoice_not_removed.status' );
}

$gAction_disp->AddEvent(
    'addrow',
    'action_addrow'
    );
function action_addrow(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],
        $eventData['invoiceid']
        );

    if ( $xen_invoice->AddRow(
        $eventData['description'],
        $eventData['amount'],
        $eventData['vatid'],
        $eventData['quantity'],
        $eventData['discount']
        ) ) $gPage_status = $gLocale->GetStr( 'row_added.status' );
}

$gAction_disp->AddEvent(
    'editrows',
    'action_editrows'
    );
function action_editrows(
    $eventData
    )
{
    global $gLocale, $gPage_status;
    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],
        $eventData['invoiceid']
        );

    $rows_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT id '.
        'FROM innowork_billing_invoices_rows '.
        'WHERE invoiceid='.$eventData['invoiceid']
        );

    while ( !$rows_query->eof )
    {
        $row_id = $rows_query->Fields( 'id' );

        $xen_invoice->EditRow(
            $row_id,
            $eventData['description'.$row_id],
            $eventData['amount'.$row_id],
            $eventData['vatid'.$row_id],
            $eventData['quantity'.$row_id],
            $eventData['discount'.$row_id]
            );

        $rows_query->MoveNext();
     }
    $gPage_status = $gLocale->GetStr( 'row_updated.status' );
}

$gAction_disp->AddEvent(
    'removerow',
    'action_removerow'
    );
function action_removerow(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],
        $eventData['invoiceid']
        );

    if ( $xen_invoice->RemoveRow(
        $eventData['rowid']
        ) ) $gPage_status = $gLocale->GetStr( 'row_removed.status' );
}

$gAction_disp->AddEvent(
    'invoicepayment',
    'action_invoicepayment'
    );
function action_invoicepayment(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],
        $eventData['invoiceid']
        );

    if ( $xen_invoice->SetPaidAmount(
        $eventData['paidamount']
        ) ) $gPage_status = $gLocale->GetStr( 'invoicepayment_updated.status' );
}

$gAction_disp->AddEvent(
    'sendinvoice',
    'action_sendinvoice'
    );
function action_sendinvoice(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],
        $eventData['invoiceid']
        );

    if ( $xen_invoice->SendToEmail( $eventData['email'] ) ) $gPage_status = $gLocale->GetStr( 'invoice_sent.status' );
    else $gPage_status = $gLocale->Getstr( 'invoice_not_sent.status' );
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new HuiDispatcher( 'main' );

function invoices_list_action_builder( $pageNumber )
{
    return build_events_call_string( '', array( array(
            'main',
            'default',
            array( 'pagenumber' => $pageNumber )
        ) ) );
}

define( 'XENBILLING_FILTER_STATUS_ALL', 0 );
define( 'XENBILLING_FILTER_STATUS_PAID', 1 );
define( 'XENBILLING_FILTER_STATUS_EXPIRED', 2 );
define( 'XENBILLING_FILTER_STATUS_TOBEPAID', 3 );

$gMain_disp->AddEvent(
    'default',
    'main_default'
    );
function main_default( $eventData )
{
    global $gEnv, $gLocale, $gPage_title, $gXml_def, $gPage_status, $gXen_core;

// Account managers

$users_query = &$GLOBALS['gEnv']['root']['db']->Execute(
    'SELECT username,lname,fname '.
    'FROM users '.
    'WHERE siteid='.$GLOBALS['gEnv']['site']['serial'].' '.
    'AND username<>'.$GLOBALS['gEnv']['root']['db']->Format_Text( $GLOBALS['gEnv']['site']['id'] ).' '.
    'ORDER BY lname,fname'
    );

$gUsers[0] = $gLocale->GetStr( 'all_account_managers.label' );

while ( !$users_query->eof )
{
    $gUsers[$users_query->Fields( 'username' )] = $users_query->Fields( 'lname' ).' '.$users_query->Fields( 'fname' );
    $users_query->MoveNext();
}

$users_query->Free();

    $search_keys = array();

    if ( isset( $eventData['filter'] ) )
    {
        // Customer

        $customer_filter_sk = new HuiSessionKey(
            'customer_filter',
            array(
                'value' => $eventData['filter_customerid']
                )
            );

        if ( $eventData['filter_customerid'] != 0 ) $search_keys['customerid'] = $eventData['filter_customerid'];

        // Account manager

        $account_manager_filter_sk = new HuiSessionKey(
            'account_manager_filter',
            array(
                'value' => $eventData['filter_account_manager']
                )
            );

        if ( $eventData['filter_account_manager'] != '0' ) $search_keys['accountmanager'] = $eventData['filter_account_manager'];

        // Year

        if ( isset( $eventData['filter_year'] ) ) $search_keys['emissiondate'] = $eventData['filter_year'];

        $year_filter_sk = new HuiSessionKey(
            'year_filter',
            array(
                'value' => isset( $eventData['filter_year'] ) ? $eventData['filter_year'] : ''
                )
            );
            
          // Month

	    $month_filter_sk = new HuiSessionKey(
	        'month_filter',
	        array(
	            'value' => isset( $eventData['filter_month'] ) ? $eventData['filter_month'] : ''
	            )
	        );


        // Status

        $status_filter_sk = new HuiSessionKey(
            'status_filter',
            array(
                'value' => $eventData['filter_status']
                )
            );
    }
    else
    {
        // Customer

        $customer_filter_sk = new HuiSessionKey( 'customer_filter' );
        if (
            strlen( $customer_filter_sk->mValue )
            and $customer_filter_sk->mValue != 0
            ) $search_keys['customerid'] = $customer_filter_sk->mValue;
        $eventData['filter_customerid'] = $customer_filter_sk->mValue;

        // Account manager

        $account_manager_filter_sk = new HuiSessionKey( 'account_manager_filter' );
        if (
            strlen( $account_manager_filter_sk->mValue )
            and $account_manager_filter_sk->mValue != '0'
            ) $search_keys['accountmanager'] = $account_manager_filter_sk->mValue;
        $eventData['filter_account_manager'] = $account_manager_filter_sk->mValue;

        // Year

        $year_filter_sk = new HuiSessionKey( 'year_filter' );
        $eventData['filter_year'] = $year_filter_sk->mValue;
        
        // Month

        $month_filter_sk = new HuiSessionKey( 'month_filter' );
        $eventData['filter_month'] = $month_filter_sk->mValue;


        // Status

        $status_filter_sk = new HuiSessionKey( 'status_filter' );
        $eventData['filter_status'] = $status_filter_sk->mValue;
    }
    
////
    if ( strlen( $eventData['filter_month'] ) &&  strlen( $eventData['filter_year'] ) )
    $search_keys['emissiondate'] = $eventData['filter_year'].'-'.$eventData['filter_month'];
    elseif ( strlen( $eventData['filter_month'] ) )
    $gPage_status = $gLocale->GetStr( 'noyearmessage.status');
    elseif ( strlen( $eventData['filter_year'] ) )
    $search_keys['emissiondate'] = $year_filter_sk->mValue;

    
    if ( $eventData['filter_account_manager'] == '0' ) unset( $eventData['filter_account_manager'] );

    if ( !count( $search_keys ) ) $search_keys = '';

    // Sorting

    $tab_sess = new HuiSessionKey( 'xenprojecttab' );

    if ( !isset( $eventData['done'] ) ) $eventData['done'] = $tab_sess->mValue;
    if ( !strlen( $eventData['done'] ) ) $eventData['done'] = 'false';

    $tab_sess = new HuiSessionKey(
        'xenprojecttab',
        array(
            'value' => $eventData['done']
            )
        );

    $country = new LocaleCountry(
        $GLOBALS['gEnv']['user']['locale']['country']
        );

    $summaries = $gXen_core->GetSummaries();

    $table = new HuiTable( 'invoices' );

    $sort_by = 0;
    if ( strlen( $table->mSortDirection ) ) $sort_order = $table->mSortDirection;
    else $sort_order = 'down';

    if ( isset( $eventData['sortby'] ) )
    {
        if ( $table->mSortBy == $eventData['sortby'] )
        {
            $sort_order = $sort_order == 'down' ? 'up' : 'down';
        }
        else
        {
            $sort_order = 'down';
        }

        $sort_by = $eventData['sortby'];
    }
    else
    {
        if ( strlen( $table->mSortBy ) ) $sort_by = $table->mSortBy;
    }

    $invoices = new XenInvoice(
        $gEnv['root']['db'],
        $gEnv['site']['db']
        );

    switch ( $sort_by )
    {
    case '0':
        $invoices->mSearchOrderBy = 'number'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '1':
        $invoices->mSearchOrderBy = 'emissiondate'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '2':
        $invoices->mSearchOrderBy = 'customerid'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '3':
        $invoices->mSearchOrderBy = 'total'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '4':
        $invoices->mSearchOrderBy = 'duedate'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '5':
        $invoices->mSearchOrderBy = 'paidamount'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    }

    $headers[0]['label'] = $gLocale->GetStr( 'number.header' );
    $headers[0]['link'] = build_events_call_string( '',
            array( array(
                    'main',
                    'default',
                    array( 'sortby' => '0' )
                    ) ) );
    $headers[1]['label'] = $gLocale->GetStr( 'emissiondate.header' );
    $headers[1]['link'] = build_events_call_string( '',
            array( array(
                    'main',
                    'default',
                    array( 'sortby' => '1' )
                    ) ) );
    $headers[2]['label'] = $gLocale->GetStr( 'customer.header' );
    $headers[2]['link'] = build_events_call_string( '',
            array( array(
                    'main',
                    'default',
                    array( 'sortby' => '2' )
                    ) ) );
    $headers[3]['label'] = $gLocale->GetStr( 'total.header' );
    $headers[3]['link'] = build_events_call_string( '',
            array( array(
                    'main',
                    'default',
                    array( 'sortby' => '3' )
                    ) ) );
    $headers[4]['label'] = $gLocale->GetStr( 'duedate.header' );
    $headers[4]['link'] = build_events_call_string( '',
            array( array(
                    'main',
                    'default',
                    array( 'sortby' => '4' )
                    ) ) );
    $headers[5]['label'] = $gLocale->GetStr( 'paidamount.header' );
    $headers[5]['link'] = build_events_call_string( '',
            array( array(
                    'main',
                    'default',
                    array( 'sortby' => '5' )
                    ) ) );
    $headers[6]['label'] = $gLocale->GetStr( 'credit.header' );

    $search_results = $invoices->Search(
        $search_keys,
        $gEnv['user']['serial']
        );
        
    $num_invoices = count( $search_results );

    $xen_customers = new XenDirectoryCompany(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db']
        );

    $customers_search = $xen_customers->Search(
        '',
        $GLOBALS['gEnv']['user']['serial']
        );

    $customers[0] = $gLocale->GetStr( 'all_customers.label' );

    foreach ( $customers_search as $id => $data )
    {
        if ($data['companytype']==XENDIRECTORY_COMPANY_TYPE_CUSTOMER or $data['companytype']==XENDIRECTORY_COMPANY_TYPE_BOTH )
        $customers[$id] = $data['companyname'];
    }

    $statuses[XENBILLING_FILTER_STATUS_ALL] = $gLocale->GetStr( 'filter_status_all.label' );
    $statuses[XENBILLING_FILTER_STATUS_PAID] = $gLocale->GetStr( 'filter_status_paid.label' );
    $statuses[XENBILLING_FILTER_STATUS_TOBEPAID] = $gLocale->GetStr( 'filter_status_tobepaid.label' );
    $statuses[XENBILLING_FILTER_STATUS_EXPIRED] = $gLocale->GetStr( 'filter_status_expired.label' );

    unset( $invoices );
    unset( $xen_customers );
    unset( $customers_search );

        $locale_country = new LocaleCountry( $GLOBALS['gEnv']['user']['locale']['country'] );

        $gXml_def =
'<vertgroup><name>invoices</name>
  <children>

    <label><name>filter</name>
      <args>
        <bold>true</bold>
        <label type="encoded">'.urlencode( $gLocale->GetStr( 'filter.label' ) ).'</label>
      </args>
    </label>

    <form><name>filter</name>
      <args>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                array(
                    'main',
                    'default',
                    array(
                        'filter' => 'true'
                        )
                    )
            ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

    <label row="0" col="0"><name>year</name>
      <args>
        <label type="encoded">'.urlencode( $gLocale->GetStr( 'filter_year.label' ) ).'</label>
      </args>
    </label>
    
    <horizgroup row="0" col="1">
    <children>

    <string><name>filter_year</name>
      <args>
        <disp>main</disp>
        <size>4</size>
        <value type="encoded">'.urlencode( isset( $eventData['filter_year'] ) ? $eventData['filter_year'] : '' ).'</value>
      </args>
    </string>

    <string><name>filter_month</name>
      <args>
        <disp>main</disp>
        <size>2</size>
        <value type="encoded">'.urlencode( isset( $eventData['filter_month'] ) ? $eventData['filter_month'] : '' ).'</value>
      </args>
    </string>

    </children>
    </horizgroup>

        <button row="0" col="2"><name>filter</name>
          <args>
            <themeimage>filter</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>filter</formsubmit>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'filter.submit' ) ).'</label>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                array(
                    'main',
                    'default',
                    array(
                        'filter' => 'true'
                        )
                    )
            ) ) ).'</action>
          </args>
        </button>

    <label row="1" col="0"><name>customer</name>
      <args>
        <label type="encoded">'.urlencode( $gLocale->GetStr( 'filter_customer.label' ) ).'</label>
      </args>
    </label>
    <combobox row="1" col="1"><name>filter_customerid</name>
      <args>
        <disp>main</disp>
        <elements type="array">'.huixml_encode( $customers ).'</elements>
        <default type="encoded">'.urlencode( isset( $eventData['filter_customerid'] ) ? $eventData['filter_customerid'] : '' ).'</default>
      </args>
    </combobox>

    <label row="2" col="0">
      <args>
        <label type="encoded">'.urlencode( $gLocale->GetStr( 'filter_account_manager.label' ) ).'</label>
      </args>
    </label>
    <combobox row="2" col="1"><name>filter_account_manager</name>
      <args>
        <disp>main</disp>
        <elements type="array">'.huixml_encode( $gUsers ).'</elements>
        <default type="encoded">'.urlencode( isset( $eventData['filter_account_manager'] ) ? $eventData['filter_account_manager'] : '' ).'</default>
      </args>
    </combobox>

    <label row="3" col="0"><name>status</name>
      <args>
        <label type="encoded">'.urlencode( $gLocale->GetStr( 'filter_status.label' ) ).'</label>
      </args>
    </label>
    <combobox row="3" col="1"><name>filter_status</name>
      <args>
        <disp>main</disp>
        <elements type="array">'.huixml_encode( $statuses ).'</elements>
        <default type="encoded">'.urlencode( isset( $eventData['filter_status'] ) ? $eventData['filter_status'] : '' ).'</default>
      </args>
    </combobox>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

    <label><name>title</name>
      <args>
        <bold>true</bold>
        <label type="encoded">'.urlencode( $gLocale->GetStr( 'invoices.label' ) ).'</label>
      </args>
    </label>';

    if ( $search_results )
    {
        $gXml_def .=
'    <table><name>invoices</name>
      <args>
        <headers type="array">'.huixml_encode( $headers ).'</headers>
        <rowsperpage>10</rowsperpage>
        <pagesactionfunction>invoices_list_action_builder</pagesactionfunction>
        <pagenumber>'.( isset( $eventData['pagenumber'] ) ? $eventData['pagenumber'] : '' ).'</pagenumber>
        <sortby>'.$sort_by.'</sortby>
        <sortdirection>'.$sort_order.'</sortdirection>
        <rows>'.$num_invoices.'</rows>
      </args>
      <children>';

        $row = 0;
        $credit = 0;
        $due_credit = 0;
        $invoices_amount = 0;

    $page = 1;

                    if ( isset( $eventData['pagenumber'] ) )
                    {
                        $page = $eventData['pagenumber'];
                    }
                    else
                    {
                        OpenLibrary( 'table.hui', HANDLER_PATH );

                        $table = new HuiTable(
                            'invoices'
                            );

                        $page = $table->mPageNumber;
                    }

                    if ( $page > ceil( $num_invoices / 10 ) ) $page = ceil( $num_invoices /10 );

                    $from = ( $page * 10 ) - 10;
                    $to = $from + 10 - 1;

        $xen_core = new XenCore(
            $GLOBALS['gEnv']['root']['db'],
            $GLOBALS['gEnv']['site']['db']
            );

        $summaries = $xen_core->GetSummaries();

        while ( list( $id, $fields ) = each( $search_results ) )
        {
            $expired = false;
            // Due date

            $due_date_array = $GLOBALS['gEnv']['site']['db']->GetDateArrayFromTimestamp( $fields['duedate'] );
            $due_date = $locale_country->FormatShortArrayDate( $due_date_array );

            if ( ( $fields['total'] - $fields['paidamount'] ) > 0 )
            {
                if (
                    $due_date_array['year'] < date( 'Y' )
                    or
                    (
                        $due_date_array['year'] == date( 'Y' )
                        and
                        $due_date_array['mon'] < date( 'm' )
                    )
                    or
                    (
                        $due_date_array['year'] == date( 'Y' )
                        and
                        $due_date_array['mon'] == date( 'm' )
                        and
                        $due_date_array['mday'] < date( 'd' )
                    )
                    )
                {
                    $expired = true;
                    //$due_date = '<font color="red">'.$due_date.'</font>';
                }
            }

            if (
                $eventData['filter_status'] == XENBILLING_FILTER_STATUS_ALL
                or ( $eventData['filter_status'] == XENBILLING_FILTER_STATUS_PAID and !$expired and ( ( $fields['total'] - $fields['paidamount'] ) == 0 ) )
                or ( $eventData['filter_status'] == XENBILLING_FILTER_STATUS_EXPIRED and $expired )
                or ( $eventData['filter_status'] == XENBILLING_FILTER_STATUS_TOBEPAID and !$expired and ( ( $fields['total'] - $fields['paidamount'] ) <> 0 ) )
                )
            {
                if ( $row >= $from and $row <= $to )
                {

                    $tmp_customer = new XenDirectoryCompany(
                        $gEnv['root']['db'],
                        $gEnv['site']['db'],
                        $fields['customerid']
                        );

                    $tmp_customer_data = $tmp_customer->GetItem();

                    $tmp_project = new XenProject(
                        $gEnv['root']['db'],
                        $gEnv['site']['db'],
                        $fields['projectid']
                        );

                    $tmp_project_data = $tmp_project->GetItem();
                }
                
                // Credit

                $credit += $fields['total'] - $fields['paidamount'];
                if ( $expired ) $due_credit += $fields['total'] - $fields['paidamount'];

                $invoices_amount += $fields['amount'];

if ( $row >= $from and $row <= $to )
{
            $gXml_def .=
'<label row="'.$row.'" col="0"><name>number</name>
  <args>
    <label type="encoded">'.urlencode( $fields['number'] ).'</label>
  </args>
</label>
<label row="'.$row.'" col="1"><name>emissiondate</name>
  <args>
    <label type="encoded">'.urlencode(
        $locale_country->FormatShortArrayDate(
            $GLOBALS['gEnv']['site']['db']->GetDateArrayFromTimestamp(
                $fields['emissiondate']
                ) ) ).'</label>
  </args>
</label>
<vertgroup row="'.$row.'" col="2"><name>customer</name>
  <children>

<link><name>customer</name>
  <args>
    <link type="encoded">'.urlencode( build_events_call_string(
        $summaries['directorycompany']['adminpage'],
        array(
            array(
                $summaries['directorycompany']['showdispatcher'],
                $summaries['directorycompany']['showevent'],
                array( 'id' => $fields['customerid'] )
                )
            )
        ) ).'</link>
      <compact>true</compact>
    <label type="encoded">'.urlencode( '<strong>'.$tmp_customer_data['companyname'].'</strong>' ).'</label>
  </args>
</link>

<link><name>project</name>
  <args>
    <link type="encoded">'.urlencode( build_events_call_string(
        $summaries['project']['adminpage'],
        array(
            array(
                $summaries['project']['showdispatcher'],
                $summaries['project']['showevent'],
                array( 'id' => $fields['projectid'] )
                )
            )
        ) ).'</link>
      <compact>true</compact>
    <label type="encoded">'.urlencode( $tmp_project_data['name'] ).'</label>
  </args>
</link>

  </children>
</vertgroup>
<label row="'.$row.'" col="3" halign="right"><name>total</name>
  <args>
    <label type="encoded">'.urlencode( number_format(
                        $fields['total'],
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    ) ).'</label>
  </args>
</label>
<label row="'.$row.'" col="4"><name>duedate</name>
  <args>
    <label type="encoded">'.urlencode( $due_date ).'</label>
  </args>
</label>
<label row="'.$row.'" col="5" halign="right"><name>paidamount</name>
  <args>
    <label type="encoded">'.urlencode( number_format(
                        $fields['paidamount'],
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    ) ).'</label>
  </args>
</label>
<label row="'.$row.'" col="6" halign="right"><name>diff</name>
  <args>
    <label type="encoded">'.urlencode( ( $expired ? '<font color="red">' : '' ).number_format(
                        $fields['total'] - $fields['paidamount'],
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    ).( $expired ? '</font>' : '' ) ).'</label>
    <bold>'.( $expired ? 'true' : 'false' ).'</bold>
  </args>
</label>
<amptoolbar row="'.$row.'" col="7"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.huixml_encode( array(
        'main' => array(
            'show' => array(
                'label' => $gLocale->GetStr( 'showinvoice.button' ),
                'themeimage' => 'zoom',
                'horiz' => 'true',
                'action' => build_events_call_string( '', array( array(
                    'main',
                    'showinvoice',
                    array( 'id' => $id ) ) ) )
                ),
            'print' => array(
                'label' => $gLocale->GetStr( 'printinvoice.submit' ),
                'themeimage' => 'fileprint',
                'horiz' => 'true',
                'target' => '_blank',
                'action' => build_events_call_string( '', array( array(
                    'main',
                    'printinvoice',
                    array( 'id' => $id ) ) ) )
                ),
            'send' => array(
                'label' => $gLocale->GetStr( 'sendinvoice.button' ),
                'themeimage' => 'mail_send',
                'horiz' => 'true',
                'action' => build_events_call_string( '', array( array(
                    'main',
                    'sendinvoice',
                    array( 'id' => $id ) ) ) )
                ),
            'payment' => array(
                'label' => $gLocale->GetStr( 'invoicepayment.button' ),
                'themeimage' => 'folder',
                'horiz' => 'true',
                'action' => build_events_call_string( '', array( array(
                    'main',
                    'invoicepayment',
                    array( 'id' => $id ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->GetStr( 'removeinvoice.button' ),
                'themeimage' => 'trash',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->GetStr( 'removeinvoice.confirm' ),
                'action' => build_events_call_string( '', array(
                    array(
                        'main',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'removeinvoice',
                        array( 'id' => $id ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</amptoolbar>';
}

            $row++;
            }
        }

        $gXml_def .=
'      </children>
    </table>

    <horizbar/>';
    }
    else
    {
        $gPage_status = $gLocale->GetStr( 'noinvoices.status' );
    }

    $gXml_def .=
'    <grid>
      <children>

        <label row="0" col="0">
          <args>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'total_invoices.label' ) ).'</label>
          </args>
        </label>
        <string row="0" col="1">
          <args>
            <readonly>true</readonly>
            <value type="encoded">'.urlencode( number_format(
                        $invoices_amount,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    )
                ).'</value>
          </args>
        </string>

        <label row="1" col="0">
          <args>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'total_credit.label' ) ).'</label>
          </args>
        </label>
        <string row="1" col="1">
          <args>
            <readonly>true</readonly>
            <value type="encoded">'.urlencode( number_format(
                        $credit,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    )
                ).'</value>
          </args>
        </string>

        <label row="2" col="0">
          <args>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'total_expired_credit.label' ) ).'</label>
          </args>
        </label>
        <string row="2" col="1">
          <args>
            <readonly>true</readonly>
            <value type="encoded">'.urlencode( number_format(
                        $due_credit,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    )
                ).'</value>
          </args>
        </string>

      </children>
    </grid>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent(
    'newinvoice',
    'main_newinvoice'
    );
function main_newinvoice( $eventData )
{
    global $gEnv, $gXml_def, $gLocale, $gPage_title;

    // Companies list

    $xen_companies = new XenDirectoryCompany(
        $gEnv['root']['db'],
        $gEnv['site']['db']
        );
    $search_results = $xen_companies->Search(
        '',
        $gEnv['user']['serial']
        );

    $companies['0'] = $gLocale->GetStr( 'nocompany.label' );

    while ( list( $id, $fields ) = each( $search_results ) )
    {
        if ($fields['companytype']==XENDIRECTORY_COMPANY_TYPE_CUSTOMER or $fields['companytype']==XENDIRECTORY_COMPANY_TYPE_BOTH )
        $companies[$id] = $fields['companyname'];
    }

    // Projects list

    $xen_projects = new XenProject(
        $gEnv['root']['db'],
        $gEnv['site']['db']
        );
    $search_results = $xen_projects->Search(
        '',
        $gEnv['user']['serial']
        );

    $projects['0'] = $gLocale->GetStr( 'noproject.label' );

    while ( list( $id, $fields ) = each( $search_results ) )
    {
        $projects[$id] = $fields['name'];
    }

    $payments_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT * '.
        'FROM innowork_billing_payments '.
        'ORDER BY description'
        );

    $payments['0'] = $gLocale->GetStr( 'nopayment.label' );

    while ( !$payments_query->eof )
    {
        $payments[$payments_query->Fields( 'id' )] = $payments_query->Fields( 'description' );
        $payments_query->MoveNext();
    }

    // Invoice number

    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db']
        );

    $invoice_number = (int)$xen_invoice->GetLastInvoiceNumber();
    $invoice_number++;

    // Emission date

    $locale_country = new LocaleCountry(
        $GLOBALS['gEnv']['user']['locale']['country']
        );

    $curr_date = $locale_country->GetDateArrayFromSafeTimestamp(
        $locale_country->SafeFormatTimestamp()
        );

    // Defaults

    $sets = new XenBilling_SettingsHandler();

    $gXml_def .=
'<vertgroup><name>newinvoice</name>
  <children>

    <table><name>invoice</name>
      <args>
        <headers type="array">'.huixml_encode(
            array( '0' => array(
                'label' => $gLocale->GetStr( 'newinvoice.label' )
                ) ) ).'</headers>
      </args>
      <children>

    <form row="0" col="0"><name>invoice</name>
      <args>
        <method>post</method>
        <action type="encoded">'.urlencode( build_events_call_string( '', array(
                array(
                    'main',
                    'showinvoice',
                    ''
                    ),
                array(
                    'action',
                    'newinvoice',
                    '' )
            ) ) ).'</action>
      </args>
      <children>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>company</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'customer.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>customerid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.huixml_encode( $companies ).'</elements>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizbar/>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>number</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'number.label' ) ).'</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <disp>action</disp>
                <size>6</size>
                <value type="encoded">'.urlencode( $invoice_number ).'</value>
              </args>
            </string>

            <label><name>emissiondate</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'emissiondate.label' ) ).'</label>
              </args>
            </label>
            <date><name>emissiondate</name>
              <args>
                <disp>action</disp>
                <value type="array">'.huixml_encode( $curr_date ).'</value>
              </args>
            </date>

          </children>
        </horizgroup>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>payment</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'payment.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>paymentid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.huixml_encode( $payments ).'</elements>
                <default>'.$sets->GetDefaultPayment().'</default>
              </args>
            </combobox>

            <!--
            <label><name>duedate</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'duedate.label' ) ).'</label>
              </args>
            </label>
            <date><name>duedate</name>
              <args>
                <disp>action</disp>
              </args>
            </date>
            -->

          </children>
        </horizgroup>

        </children>
        </form>

        <horizbar/>

        <button row="1" col="0"><name>apply</name>
          <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>invoice</formsubmit>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'newinvoice.submit' ) ).'</label>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                array(
                    'main',
                    'showinvoice',
                    ''
                    ),
                array(
                    'action',
                    'newinvoice',
                    '' )
            ) ) ).'</action>
          </args>
        </button>

      </children>
    </table>
  </children>
</vertgroup>';
}

$gMain_disp->AddEvent(
    'showinvoice',
    'main_showinvoice'
    );
function main_showinvoice( $eventData )
{
    global $gEnv, $gXml_def, $gLocale, $gPage_title;

    $locale_country = new LocaleCountry(
        $GLOBALS['gEnv']['site']['locale']['country']
        );

    if ( isset( $GLOBALS['gEnv']['runtime']['xen-billing']['newinvoiceid'] ) )
    {
        $eventData['id'] = $GLOBALS['gEnv']['runtime']['xen-billing']['newinvoiceid'];
    }

    $xen_invoice = new Xeninvoice(
        $gEnv['root']['db'],
        $gEnv['site']['db'],
        $eventData['id']
        );

    $inv_data = $xen_invoice->GetItem( $GLOBALS['gEnv']['user']['serial'] );
    // Companies list

    $xen_customer = new XenDirectoryCompany(
        $gEnv['root']['db'],
        $gEnv['site']['db'],
        $inv_data['customerid']
        );
    $search_results = $xen_customer->Search(
        '',
        $gEnv['user']['serial']
        );

    $cust_data = $xen_customer->GetItem();

    $companies['0'] = $gLocale->GetStr( 'nocompany.label' );

    while ( list( $id, $fields ) = each( $search_results ) )
    {
        if ($fields['companytype']==XENDIRECTORY_COMPANY_TYPE_CUSTOMER or $fields['companytype']==XENDIRECTORY_COMPANY_TYPE_BOTH )
        $companies[$id] = $fields['companyname'];
    }

    // Projects list

    $xen_projects = new XenProject(
        $gEnv['root']['db'],
        $gEnv['site']['db']
        );
    $search_results = $xen_projects->Search(
        array( 'customerid' => $inv_data['customerid'] ),
        $gEnv['user']['serial']
        );

    $projects['0'] = $gLocale->GetStr( 'noproject.label' );

    while ( list( $id, $fields ) = each( $search_results ) )
    {
        $projects[$id] = $fields['name'];
    }

// Account managers

$users_query = &$GLOBALS['gEnv']['root']['db']->Execute(
    'SELECT username,lname,fname '.
    'FROM users '.
    'WHERE siteid='.$GLOBALS['gEnv']['site']['serial'].' '.
    'AND username<>'.$GLOBALS['gEnv']['root']['db']->Format_Text( $GLOBALS['gEnv']['site']['id'] ).' '.
    'ORDER BY lname,fname'
    );

$gUsers[''] = $gLocale->GetStr( 'no_account_manager.label' );

while ( !$users_query->eof )
{
    $gUsers[$users_query->Fields( 'username' )] = $users_query->Fields( 'lname' ).' '.$users_query->Fields( 'fname' );
    $users_query->MoveNext();
}

$users_query->Free();

    // Payments

    $payments_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT * '.
        'FROM innowork_billing_payments '.
        'ORDER BY description'
        );

    $payments['0'] = $gLocale->GetStr( 'nopayment.label' );

    while ( !$payments_query->eof )
    {
        $payments[$payments_query->Fields( 'id' )] = $payments_query->Fields( 'description' );
        $payments_query->MoveNext();
    }

    // Due date


    $rows_headers[0]['label'] = $gLocale->GetStr( 'row_description.header' );
    $rows_headers[1]['label'] = $gLocale->GetStr( 'row_amount.header' );
    $rows_headers[2]['label'] = $gLocale->GetStr( 'row_quantiy.header' );
    $rows_headers[3]['label'] = $gLocale->GetStr( 'row_discount.header' );
    $rows_headers[4]['label'] = $gLocale->GetStr( 'row_vat.header' );
    $rows_headers[5]['label'] = $gLocale->GetStr( 'row_total.header' );

    $gXml_def .=
'<vertgroup><name>showinvoice</name>
  <children>

    <table><name>invoice</name>
      <args>
        <headers type="array">'.huixml_encode(
            array( '0' => array(
                'label' => $gLocale->GetStr( 'showinvoice.label' )
                ) ) ).'</headers>
      </args>
      <children>

    <form row="0" col="0"><name>invoice</name>
      <args>
        <method>post</method>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'showinvoice',
                        array( 'id' => $eventData['id'] )
                        ),
                    array(
                        'action',
                        'editinvoice',
                        array( 'id' => $eventData['id'] ) )
                ) ) ).'</action>
      </args>
      <children>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>company</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'customer.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>customerid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.huixml_encode( $companies ).'</elements>
                <default>'.$inv_data['customerid'].'</default>
              </args>
            </combobox>

            <label><name>project</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'project.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>projectid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.huixml_encode( $projects ).'</elements>
                <default>'.$inv_data['projectid'].'</default>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizgroup>
          <children>

            <label>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'account_manager.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>accountmanager</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.huixml_encode( $gUsers ).'</elements>
                <default>'.$inv_data['accountmanager'].'</default>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizbar/>

        <horizgroup>
          <children>

            <label><name>street</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'street.label' ) ).'</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <readonly>true</readonly>
                <size>25</size>
                <value type="encoded">'.urlencode( $cust_data['street'] ).'</value>
              </args>
            </string>

            <label><name>city</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'city.label' ) ).'</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <readonly>true</readonly>
                <size>15</size>
                <value type="encoded">'.urlencode( $cust_data['city'] ).'</value>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizgroup>
          <children>

            <label><name>zip</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'zip.label' ) ).'</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <readonly>true</readonly>
                <size>5</size>
                <value type="encoded">'.urlencode( $cust_data['zip'] ).'</value>
              </args>
            </string>

            <label><name>state</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'state.label' ) ).'</label>
              </args>
            </label>
            <string><name>state</name>
              <args>
                <readonly>true</readonly>
                <size>2</size>
                <value type="encoded">'.urlencode( $cust_data['state'] ).'</value>
              </args>
            </string>

            <label><name>country</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'country.label' ) ).'</label>
              </args>
            </label>
            <string><name>country</name>
              <args>
                <readonly>true</readonly>
                <size>15</size>
                <value type="encoded">'.urlencode( $cust_data['country'] ).'</value>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizgroup>
          <children>

            <label><name>fiscalcode</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'fiscalcode.label' ) ).'</label>
              </args>
            </label>
            <string><name>fiscalcode</name>
              <args>
                <readonly>true</readonly>
                <size>14</size>
                <value type="encoded">'.urlencode( $cust_data['fiscalcode'] ).'</value>
              </args>
            </string>

          </children>
        </horizgroup>


        <horizbar/>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>number</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'number.label' ) ).'</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <disp>action</disp>
                <size>6</size>
                <value type="encoded">'.urlencode( $inv_data['number'] ).'</value>
              </args>
            </string>

            <label><name>emissiondate</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'emissiondate.label' ) ).'</label>
              </args>
            </label>
            <date><name>emissiondate</name>
              <args>
                <disp>action</disp>
                <value type="array">'.huixml_encode(
                    $GLOBALS['gEnv']['site']['db']->GetDateArrayFromTimestamp(
                        $inv_data['emissiondate'] ) ).'</value>
                <type>date</type>
              </args>
            </date>

          </children>
        </horizgroup>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>payment</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'payment.label' ) ).'</label>
              </args>
            </label>
            <combobox><name>paymentid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.huixml_encode( $payments ).'</elements>
                <default>'.$inv_data['paymentid'].'</default>
              </args>
            </combobox>

            <label><name>duedate</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'duedate.label' ) ).'</label>
              </args>
            </label>
            <date><name>duedate</name>
              <args>
                <disp>action</disp>
                <value type="array">'.huixml_encode(
                    $GLOBALS['gEnv']['site']['db']->GetDateArrayFromTimestamp(
                        $inv_data['duedate'] ) ).'</value>
                <type>date</type>
              </args>
            </date>

          </children>
        </horizgroup>

        <horizbar/>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>amount</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'amount.label' ) ).'</label>
              </args>
            </label>
            <string><name>vat</name>
              <args>
                <size>10</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode(
                    number_format(
                        $inv_data['amount'],
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    ) ).'</value>
              </args>
            </string>

            <label><name>vat</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'vat.label' ) ).'</label>
              </args>
            </label>
            <string><name>vat</name>
              <args>
                <size>10</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode(
                    number_format(
                        $inv_data['vat'],
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    ) ).'</value>
              </args>
            </string>

            <label><name>total</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'total.label' ) ).'</label>
              </args>
            </label>
            <string><name>total</name>
              <args>
                <size>10</size>
                <readonly>true</readonly>
                <value type="encoded">'.urlencode(
                    number_format(
                        $inv_data['total'],
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                    ) ).'</value>
              </args>
            </string>

          </children>
        </horizgroup>

        </children>
        </form>

        <horizgroup row="1" col="0">
          <children>

        <button><name>apply</name>
          <args>
            <themeimage>filesave</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'showinvoice',
                        array( 'id' => $eventData['id'] )
                        ),
                    array(
                        'action',
                        'editinvoice',
                        array( 'id' => $eventData['id'] ) )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'updateinvoice.submit' ) ).'</label>
            <formsubmit>invoice</formsubmit>
          </args>
        </button>

        <button><name>close</name>
          <args>
            <themeimage>fileclose</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'default'
                        )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'closeinvoice.submit' ) ).'</label>
          </args>
        </button>

        <button><name>print</name>
          <args>
            <themeimage>fileprint</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <target>_blank</target>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'printinvoice',
                        array( 'id' => $eventData['id'] )
                        )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'printinvoice.submit' ) ).'</label>
          </args>
        </button>

        <button><name>send</name>
          <args>
            <themeimage>mail_send</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'sendinvoice',
                        array( 'id' => $eventData['id'] )
                        )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'sendinvoice.button' ) ).'</label>
          </args>
        </button>

        <button><name>remove</name>
          <args>
            <themeimage>trash</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <needconfirm>true</needconfirm>
            <confirmmessage type="encoded">'.urlencode( $gLocale->GetStr( 'removeinvoice.confirm' ) ).'</confirmmessage>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'default'
                        ),
                    array(
                        'action',
                        'removeinvoice',
                        array( 'id' => $eventData['id'] ) )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'removeinvoice.submit' ) ).'</label>
          </args>
        </button>

          </children>
        </horizgroup>

        <vertgroup row="2" col="0">
          <children>

        <form><name>rows</name>
          <args>
            <method>post</method>
            <action type="encoded">'.urlencode(
                build_events_call_string( '', array(
                    array(
                        'main',
                        'showinvoice',
                        array( 'id' => $eventData['id'] )
                        ),
                    array(
                        'action',
                        'editrows',
                        array(
                            'invoiceid' => $eventData['id']
                            )
                    ) ) )
                ).'</action>
          </args>
          <children>

            <table><name>rows</name>
              <args>
                <headers type="array">'.huixml_encode( $rows_headers ).'</headers>
              </args>
              <children>';

	$row_list = $xen_invoice->GetRows();
	
    $vats_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT * '.
        'FROM innowork_billing_vat_codes '.
        'ORDER BY vat'
        );

    $vats['0'] = $gLocale->GetStr( 'novat.label' );
    $vats_perc = array();

    while ( !$vats_query->eof )
    {
        $vats[$vats_query->Fields( 'id' )] = $vats_query->Fields( 'vat' );
        $vats_perc[$vats_query->Fields( 'id' )] = $vats_query->Fields( 'percentual' );
        $vats_query->MoveNext();
    }

    $row = 0;
    unset( $row_list['amount'] );
	foreach ( $row_list as $row_data )
    {
        $gXml_def .=
'<string row="'.$row.'" col="0"><name>description'.$row_data['id'].'</name>
  <args>
    <disp>action</disp>
    <size>40</size>
    <value type="encoded">'.urlencode( $row_data['description'] ).'</value>
  </args>
</string>
<string row="'.$row.'" col="1"><name>amount'.$row_data['id'].'</name>
  <args>
    <disp>action</disp>
    <size>10</size>
    <value type="encoded">'.urlencode( $row_data['unf_amount'] ).'</value>
  </args>
</string>
<string row="'.$row.'" col="2"><name>quantity'.$row_data['id'].'</name>
  <args>
    <disp>action</disp>
    <size>4</size>
    <value type="encoded">'.urlencode( $row_data['quantity'] ).'</value>
  </args>
</string>
<string row="'.$row.'" col="3"><name>discount'.$row_data['id'].'</name>
  <args>
    <disp>action</disp>
    <size>4</size>
    <value type="encoded">'.urlencode( $row_data['discount'] ).'</value>
  </args>
</string>
<combobox row="'.$row.'" col="4"><name>vatid'.$row_data['id'].'</name>
  <args>
    <disp>action</disp>
    <elements type="array">'.huixml_encode( $vats ).'</elements>
    <default>'.$row_data['vatid'].'</default>
  </args>
</combobox>
<label row="'.$row.'" col="5" halign="right">
  <args>
   <label type="encoded">'.urlencode( $row_data['total'] ).'</label>
  </args>
</label>
<amptoolbar row="'.$row.'" col="6"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.huixml_encode( array(
        'main' => array(
            'update' => array(
                'label' => $gLocale->GetStr( 'update_row.button' ),
                'themeimage' => 'filesave',
                'horiz' => 'true',
                'formsubmit' => 'rows',
                'action' => build_events_call_string( '', array(
                    array(
                        'main',
                        'showinvoice',
                        array( 'id' => $eventData['id'] )
                        ),
                    array(
                        'action',
                        'editrows',
                        array(
                            'invoiceid' => $eventData['id']
                            )
                    ) )
                ) ),
            'remove' => array(
                'label' => $gLocale->GetStr( 'remove_row.button' ),
                'themeimage' => 'edit_remove',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->GetStr( 'remove_row.confirm' ),
                'action' => build_events_call_string( '', array(
                    array(
                        'main',
                        'showinvoice',
                        array( 'id' => $eventData['id'] )
                    ),
                    array(
                        'action',
                        'removerow',
                        array(
                            'invoiceid' => $eventData['id'],
                            'rowid' => $row_data['id']
                            )
                    ) ) )
        ) ) ) ).'</toolbars>
  </args>
</amptoolbar>';

        $row++;
    }

    $gXml_def .=
'              </children>
            </table>

              </children>
            </form>';

    unset( $rows_headers );

    $sets = new XenBilling_SettingsHandler();
    $rows_headers[0]['label'] = $gLocale->GetStr( 'row_description.header' );
    $rows_headers[1]['label'] = $gLocale->GetStr( 'row_amount.header' );
    $rows_headers[2]['label'] = $gLocale->GetStr( 'row_quantiy.header' );
    $rows_headers[3]['label'] = $gLocale->GetStr( 'row_discount.header' );
    $rows_headers[4]['label'] = $gLocale->GetStr( 'row_vat.header' );

        $gXml_def .=
'<horizbar/>

<form><name>addrow</name>
          <args>
            <method>post</method>
            <action type="encoded">'.urlencode(
                build_events_call_string( '', array(
                    array(
                        'main',
                        'showinvoice',
                        array( 'id' => $eventData['id'] )
                        ),
                    array(
                        'action',
                        'addrow',
                        array(
                            'invoiceid' => $eventData['id']
                            )
                    ) )
                )
                ).'</action>
          </args>
  <children>
            <table><name>rows</name>
              <args>
                <headers type="array">'.huixml_encode( $rows_headers ).'</headers>
              </args>
              <children>
<string row="0" col="0"><name>description</name>
  <args>
    <disp>action</disp>
    <size>40</size>
  </args>
</string>
<string row="0" col="1"><name>amount</name>
  <args>
    <disp>action</disp>
    <size>10</size>
  </args>
</string>
<string row="0" col="2"><name>quantity</name>
  <args>
    <disp>action</disp>
    <size>4</size>
  </args>
</string>
<string row="0" col="3"><name>discount</name>
  <args>
    <disp>action</disp>
    <size>4</size>
  </args>
</string>
<combobox row="0" col="4"><name>vatid</name>
  <args>
    <disp>action</disp>
    <elements type="array">'.huixml_encode( $vats ).'</elements>
    <default>'.$sets->GetDefaultVat().'</default>
  </args>
</combobox>
<amptoolbar row="0" col="5"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.huixml_encode( array(
        'main' => array(
            'add' => array(
                'label' => $gLocale->GetStr( 'add_row.button' ),
                'themeimage' => 'edit_add',
                'horiz' => 'true',
                'formsubmit' => 'addrow',
                'action' => build_events_call_string( '', array(
                    array(
                        'main',
                        'showinvoice',
                        array( 'id' => $eventData['id'] )
                        ),
                    array(
                        'action',
                        'addrow',
                        array(
                            'invoiceid' => $eventData['id']
                            )
                    ) )
                )
        ) ) ) ).'</toolbars>
  </args>
</amptoolbar>
              </children>
            </table>

          </children>
        </form>

          </children>
        </vertgroup>

      </children>
    </table>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent(
    'invoicepayment',
    'main_invoicepayment'
    );
function main_invoicepayment(
    $eventData
    )
{
    global $gLocale, $gXml_def;

    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],
        $eventData['id']
        );

    $inv_data = $xen_invoice->GetItem();

    $gXml_def =
'<vertgroup>
  <children>

    <form><name>paidamount</name>
      <args>
        <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'invoicepayment',
                        array( 'invoiceid' => $eventData['id'] ) )
                ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

          <label row="0" col="0">
            <args>
              <label type="encoded">'.urlencode( $gLocale->GetStr( 'invoice_amount.label' ) ).'</label>
            </args>
          </label>
          <string row="0" col="1">
            <args>
              <readonly>true</readonly>
              <size>10</size>
              <disp>action</disp>
              <value type="encoded">'.urlencode( $inv_data['total'] ).'</value>
            </args>
          </string>

          <label row="1" col="0">
            <args>
              <label type="encoded">'.urlencode( $gLocale->GetStr( 'paid_amount.label' ) ).'</label>
            </args>
          </label>
          <string row="1" col="1"><name>paidamount</name>
            <args>
              <size>10</size>
              <disp>action</disp>
              <value type="encoded">'.urlencode( $inv_data['paidamount'] ).'</value>
            </args>
          </string>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

    <button>

      <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'invoicepayment',
                        array( 'invoiceid' => $eventData['id'] ) )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'payment.submit' ) ).'</label>
            <formsubmit>paidamount</formsubmit>
          </args>

    </button>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent(
    'sendinvoice',
    'main_sendinvoice'
    );
function main_sendinvoice(
    $eventData
    )
{
    global $gLocale, $gXml_def;

    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],
        $eventData['id']
        );

    $inv_data = $xen_invoice->GetItem();

    OpenLibrary( 'xendirectory.library' );

    $xen_customer = new XenDirectoryCompany(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],
        $inv_data['customerid']
        );

    $cust_data = $xen_customer->GetItem();

    $gXml_def =
'<vertgroup>
  <children>

    <form><name>email</name>
      <args>
        <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'sendinvoice',
                        array( 'invoiceid' => $eventData['id'] ) )
                ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

          <label row="0" col="0">
            <args>
              <label type="encoded">'.urlencode( $gLocale->GetStr( 'dest_email.label' ) ).'</label>
            </args>
          </label>
          <string row="0" col="1"><name>email</name>
            <args>
              <disp>action</disp>
              <size>25</size>
              <value type="encoded">'.urlencode( $cust_data['email'] ).'</value>
            </args>
          </string>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

    <button>

      <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'sendinvoice',
                        array( 'invoiceid' => $eventData['id'] ) )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'send.submit' ) ).'</label>
            <formsubmit>email</formsubmit>
          </args>

    </button>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent(
    'printinvoice',
    'main_printinvoice'
    );
function main_printinvoice(
    $eventData
    )
{
    $xen_invoice = new XenInvoice(
        $GLOBALS['gEnv']['root']['db'],
        $GLOBALS['gEnv']['site']['db'],

        $eventData['id']
        );

    echo $xen_invoice->CreateHtmlInvoice();

    exit();
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gHui->AddChild( new HuiAmpPage( 'page', array(
    'pagetitle' => $gPage_title,
    'icon' => 'document',
    'toolbars' => array(
        new HuiAmpToolBar(
            'core',
            array(
                'toolbars' => $gCore_toolbars
                ) ),
        new HuiAmpToolbar(
            'main',
            array(
                'toolbars' => $gToolbars
                ) )
            ),
    'maincontent' => new HuiXml(
        'page', array(
            'definition' => "<?xml version='1.0' encoding='ISO-8859-1'?>\n".$gXml_def
            ) ),
    'status' => $gPage_status
    ) ) );

$gHui->Render();

?>