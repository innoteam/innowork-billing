<?php
require_once 'innomatic/wui/Wui.php';

global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gInnowork_core;

$gInnowork_core = \Innowork\Core\InnoworkCore::instance('\Innowork\Core\InnoworkCore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

$gLocale = new \Innomatic\Locale\LocaleCatalog('innowork-billing::main', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());

$gWui = Wui::instance('wui');
$gWui->loadAllWidgets();

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr('invoices.title');
$gCore_toolbars = $gInnowork_core->GetMainToolBar();
$gToolbars['invoices'] = array(
    'invoices' => array(
        'label' => $gLocale->getStr('invoices.toolbar'),
        'themeimage' => 'listbulletleft',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('', array(
            array(
                'view',
                'default',
                ''
            )
        ))
    ),
    'newinvoice' => array(
        'label' => $gLocale->getStr('newinvoice.toolbar'),
        'themeimage' => 'documentnew',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('', array(
            array(
                'view',
                'newinvoice',
                ''
            )
        ))
    )
);

$gToolbars['prefs'] = array(
    'prefs' => array(
        'label' => $gLocale->getStr('preferences.toolbar'),
        'themeimage' => 'settings1',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('innoworkbillingprefs', array(
            array(
                'view',
                'default',
                ''
            )
        ))
    )
);

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher('action');

$gAction_disp->AddEvent('newinvoice', 'action_newinvoice');

function action_newinvoice($eventData)
{
    global $gLocale, $gPage_status;

    $xen_project = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

    if ($eventData['customerid']) {
        $customer = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['customerid']);

        $customer_data = $customer->GetItem();

        $eventData['accountmanager'] = $customer_data['accountmanager'];
    }

    if ($eventData['paymentid']) {
        $tmp_payment = new \Innowork\Billing\InnoworkBillingPayment($eventData['paymentid']);

        $country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

        $date_array = $country->GetDateArrayFromShortDateStamp($eventData['emissiondate']);
        $emission_date_tstamp = mktime(0, 0, 0, $date_array['mon'], $date_array['mday'], $date_array['year']);

        $due_date_tstamp = $emission_date_tstamp + (3600 * 24 * $tmp_payment->GetDays());

        if ($tmp_payment->GetMonthEnd()) {
            $day = date('j', $due_date_tstamp);
            $lday = date('t', $due_date_tstamp);
            $days = $lday - $day;
            $due_date_tstamp += 3600 * 24 * $days;
        }

        $due_date = $country->SafeFormatTimestamp($due_date_tstamp);

        $eventData['duedate'] = $country->FormatShortArrayDate($country->GetDateArrayFromSafeTimestamp($due_date));
    } else {
        $eventData['duedate'] = $eventData['emissiondate'];
    }

    if ($xen_project->Create($eventData, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId())) {
        $GLOBALS['gEnv']['runtime']['xen-billing']['newinvoiceid'] = $xen_project->mItemId;
        $gPage_status = $gLocale->getStr('invoice_added.status');
    } else
        $gPage_status = $gLocale->getStr('invoice_not_added.status');
}

$gAction_disp->AddEvent('editinvoice', 'action_editinvoice');

function action_editinvoice($eventData)
{
    global $gLocale, $gPage_status;

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($xen_invoice->Edit($eventData, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId()))
        $gPage_status = $gLocale->getStr('invoice_updated.status');
    else
        $gPage_status = $gLocale->getStr('invoice_not_updated.status');
}

$gAction_disp->AddEvent('removeinvoice', 'action_removeinvoice');

function action_removeinvoice($eventData)
{
    global $gLocale, $gPage_status;

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($xen_invoice->Remove(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId()))
        $gPage_status = $gLocale->getStr('invoice_removed.status');
    else
        $gPage_status = $gLocale->getStr('invoice_not_removed.status');
}

$gAction_disp->AddEvent('addrow', 'action_addrow');

function action_addrow($eventData)
{
    global $gLocale, $gPage_status;

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['invoiceid']);

    if ($xen_invoice->AddRow($eventData['description'], $eventData['amount'], $eventData['vatid'], $eventData['quantity'], $eventData['discount']))
        $gPage_status = $gLocale->getStr('row_added.status');
}

$gAction_disp->AddEvent('editrows', 'action_editrows');

function action_editrows($eventData)
{
    global $gLocale, $gPage_status;
    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['invoiceid']);

    $rows_query = &\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT id ' . 'FROM innowork_billing_invoices_rows ' . 'WHERE invoiceid=' . $eventData['invoiceid']);

    while (! $rows_query->eof) {
        $row_id = $rows_query->getFields('id');

        $xen_invoice->EditRow($row_id, $eventData['description' . $row_id], $eventData['amount' . $row_id], $eventData['vatid' . $row_id], $eventData['quantity' . $row_id], $eventData['discount' . $row_id]);

        $rows_query->MoveNext();
    }
    $gPage_status = $gLocale->getStr('row_updated.status');
}

$gAction_disp->AddEvent('removerow', 'action_removerow');

function action_removerow($eventData)
{
    global $gLocale, $gPage_status;

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['invoiceid']);

    if ($xen_invoice->RemoveRow($eventData['rowid']))
        $gPage_status = $gLocale->getStr('row_removed.status');
}

$gAction_disp->AddEvent('invoicepayment', 'action_invoicepayment');

function action_invoicepayment($eventData)
{
    global $gLocale, $gPage_status;

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['invoiceid']);

    if ($xen_invoice->SetPaidAmount($eventData['paidamount']))
        $gPage_status = $gLocale->getStr('invoicepayment_updated.status');
}

$gAction_disp->AddEvent('sendinvoice', 'action_sendinvoice');

function action_sendinvoice($eventData)
{
    global $gLocale, $gPage_status;

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['invoiceid']);

    if ($xen_invoice->SendToEmail($eventData['email']))
        $gPage_status = $gLocale->getStr('invoice_sent.status');
    else
        $gPage_status = $gLocale->Getstr('invoice_not_sent.status');
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher('view');

function invoices_list_action_builder($pageNumber)
{
    return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('pagenumber' => $pageNumber))));
}

define('XENBILLING_FILTER_STATUS_ALL', 0);
define('XENBILLING_FILTER_STATUS_PAID', 1);
define('XENBILLING_FILTER_STATUS_EXPIRED', 2);
define('XENBILLING_FILTER_STATUS_TOBEPAID', 3);

$gMain_disp->AddEvent('default', 'main_default');

function main_default($eventData)
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gInnowork_core;
    // Account managers
    $users_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->execute('SELECT username,lname,fname FROM domain_users ORDER BY lname,fname');

    $gUsers[0] = $gLocale->getStr('all_account_managers.label');

    while (! $users_query->eof) {
        $gUsers[$users_query->getFields('username')] = $users_query->getFields('lname') . ' ' . $users_query->getFields('fname');
        $users_query->moveNext();
    }

    $users_query->free();

    $search_keys = array();

    if (isset($eventData['filter'])) {
        // Customer
        $customer_filter_sk = new WuiSessionKey('customer_filter', array(
            'value' => $eventData['filter_customerid']
        ));

        // Account manager
        $account_manager_filter_sk = new WuiSessionKey('account_manager_filter', array(
            'value' => $eventData['filter_account_manager']
        ));

        $year_filter_sk = new WuiSessionKey('year_filter', array(
            'value' => isset($eventData['filter_year']) ? $eventData['filter_year'] : ''
        ));

        // Month
        $month_filter_sk = new WuiSessionKey('month_filter', array(
            'value' => isset($eventData['filter_month']) ? $eventData['filter_month'] : ''
        ));

        // Status
        $status_filter_sk = new WuiSessionKey('status_filter', array(
            'value' => $eventData['filter_statusid']
        ));
    } else {
        // Customer
        $customer_filter_sk = new WuiSessionKey('customer_filter');
        if (strlen($customer_filter_sk->mValue) and $customer_filter_sk->mValue != 0) {
            $search_keys['customerid'] = $customer_filter_sk->mValue;
        }
        $eventData['filter_customerid'] = $customer_filter_sk->mValue;

        // Account manager
        $account_manager_filter_sk = new WuiSessionKey('account_manager_filter');
        if (strlen($account_manager_filter_sk->mValue) and $account_manager_filter_sk->mValue != '0') {
            $search_keys['accountmanager'] = $account_manager_filter_sk->mValue;
        }
        $eventData['filter_account_manager'] = $account_manager_filter_sk->mValue;

        // Year
        $year_filter_sk = new WuiSessionKey('year_filter');
        $eventData['filter_year'] = $year_filter_sk->mValue;

        // Month
        $month_filter_sk = new WuiSessionKey('month_filter');
        $eventData['filter_month'] = $month_filter_sk->mValue;

        // Status
        $status_filter_sk = new WuiSessionKey('status_filter');
        $eventData['filter_status'] = $status_filter_sk->mValue;
    }

    if ($eventData['filter_customerid'] != 0) {
        $search_keys['customerid'] = $eventData['filter_customerid'];
    }

    if ($eventData['filter_account_manager'] != '0') {
        $search_keys['accountmanager'] = $eventData['filter_account_manager'];
    }

    // Year
    if (isset($eventData['filter_year'])) {
        $search_keys['emissiondate'] = $eventData['filter_year'];
    }

    if (strlen($eventData['filter_month']) && strlen($eventData['filter_year'])) {
        $search_keys['emissiondate'] = $eventData['filter_year'] . '-' . $eventData['filter_month'];
    } elseif (strlen($eventData['filter_month'])) {
        $gPage_status = $gLocale->getStr('noyearmessage.status');
    } elseif (strlen($eventData['filter_year'])) {
        $search_keys['emissiondate'] = $year_filter_sk->mValue;
    }

    if ($eventData['filter_account_manager'] == '0') {
        unset($eventData['filter_account_manager']);
    }

    if (!count($search_keys)) {
        $search_keys = '';
    }

    // Sorting
    $tab_sess = new WuiSessionKey('xenprojecttab');

    if (!isset($eventData['done'])) {
        $eventData['done'] = $tab_sess->mValue;
    }
    if (!strlen($eventData['done'])) {
        $eventData['done'] = 'false';
    }

    $tab_sess = new WuiSessionKey('xenprojecttab', array('value' => $eventData['done']));

    $country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

    $summaries = $gInnowork_core->GetSummaries();

    $table = new WuiTable('invoices');

    $sort_by = 0;
    if (strlen($table->mSortDirection)) {
        $sort_order = $table->mSortDirection;
    } else {
        $sort_order = 'down';
    }

    if (isset($eventData['sortby'])) {
        if ($table->mSortBy == $eventData['sortby']) {
            $sort_order = $sort_order == 'down' ? 'up' : 'down';
        } else {
            $sort_order = 'down';
        }

        $sort_by = $eventData['sortby'];
    } else {
        if (strlen($table->mSortBy))
            $sort_by = $table->mSortBy;
    }

    $invoices = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

    switch ($sort_by) {
        case '0':
            $invoices->mSearchOrderBy = 'id' . ($sort_order == 'up' ? ' DESC' : '');
            break;
        case '1':
            $invoices->mSearchOrderBy = 'emissiondate' . ($sort_order == 'up' ? ' DESC' : '');
            break;
        case '2':
            $invoices->mSearchOrderBy = 'customerid' . ($sort_order == 'up' ? ' DESC' : '');
            break;
        case '3':
            $invoices->mSearchOrderBy = 'total' . ($sort_order == 'up' ? ' DESC' : '');
            break;
        case '4':
            $invoices->mSearchOrderBy = 'duedate' . ($sort_order == 'up' ? ' DESC' : '');
            break;
        case '5':
            $invoices->mSearchOrderBy = 'paidamount' . ($sort_order == 'up' ? ' DESC' : '');
            break;
    }

    $headers[0]['label'] = $gLocale->getStr('number.header');
    $headers[0]['link']  = WuiEventsCall::buildEventsCallString('', array( array( 'view', 'default', array( 'sortby' => '0'))));
    $headers[1]['label'] = $gLocale->getStr('emissiondate.header');
    $headers[1]['link']  = WuiEventsCall::buildEventsCallString('', array( array( 'view', 'default', array( 'sortby' => '1'))));
    $headers[2]['label'] = $gLocale->getStr('customer.header');
    $headers[2]['link']  = WuiEventsCall::buildEventsCallString('', array( array( 'view', 'default', array( 'sortby' => '2'))));
    $headers[3]['label'] = $gLocale->getStr('total.header');
    $headers[3]['link']  = WuiEventsCall::buildEventsCallString('', array( array( 'view', 'default', array( 'sortby' => '3'))));
    $headers[4]['label'] = $gLocale->getStr('duedate.header');
    $headers[4]['link']  = WuiEventsCall::buildEventsCallString('', array( array( 'view', 'default', array( 'sortby' => '4'))));
    $headers[5]['label'] = $gLocale->getStr('paidamount.header');
    $headers[5]['link']  = WuiEventsCall::buildEventsCallString('', array( array( 'view', 'default', array( 'sortby' => '5'))));
    $headers[6]['label'] = $gLocale->getStr('credit.header');
    $search_results      = $invoices->search($search_keys, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId());

    $num_invoices = count($search_results);

    $xen_customers = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

    $customers_search = $xen_customers->search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId());

    $customers[0] = $gLocale->getStr('all_customers.label');

    foreach ($customers_search as $id => $data) {
        if ($data['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_CUSTOMER or $data['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_BOTH) {
            $customers[$id] = $data['companyname'];
        }
    }

    $statuses[XENBILLING_FILTER_STATUS_ALL]      = $gLocale->getStr('filter_status_all.label');
    $statuses[XENBILLING_FILTER_STATUS_PAID]     = $gLocale->getStr('filter_status_paid.label');
    $statuses[XENBILLING_FILTER_STATUS_TOBEPAID] = $gLocale->getStr('filter_status_tobepaid.label');
    $statuses[XENBILLING_FILTER_STATUS_EXPIRED]  = $gLocale->getStr('filter_status_expired.label');

    unset($invoices);
    unset($xen_customers);
    unset($customers_search);

    $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

    $gXml_def = '<vertgroup><name>invoices</name>
  <children>

    <label><name>filter</name>
      <args>
        <bold>true</bold>
        <label type="encoded">' . urlencode($gLocale->getStr('filter.label')) . '</label>
      </args>
    </label>

    <form><name>filter</name>
      <args>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            array(
                'filter' => 'true'
            )
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

    <label row="0" col="0"><name>year</name>
      <args>
        <label type="encoded">' . urlencode($gLocale->getStr('filter_year.label')) . '</label>
      </args>
    </label>

    <horizgroup row="0" col="1">
    <children>

    <string><name>filter_year</name>
      <args>
        <disp>view</disp>
        <size>4</size>
        <value type="encoded">' . urlencode(isset($eventData['filter_year']) ? $eventData['filter_year'] : '') . '</value>
      </args>
    </string>

    <string><name>filter_month</name>
      <args>
        <disp>view</disp>
        <size>2</size>
        <value type="encoded">' . urlencode(isset($eventData['filter_month']) ? $eventData['filter_month'] : '') . '</value>
      </args>
    </string>

    </children>
    </horizgroup>

        <button row="0" col="2"><name>filter</name>
          <args>
            <themeimage>zoom</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>filter</formsubmit>
            <label type="encoded">' . urlencode($gLocale->getStr('filter.submit')) . '</label>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            array(
                'filter' => 'true'
            )
        )
    ))) . '</action>
          </args>
        </button>

    <label row="1" col="0"><name>customer</name>
      <args>
        <label type="encoded">' . urlencode($gLocale->getStr('filter_customer.label')) . '</label>
      </args>
    </label>
    <combobox row="1" col="1"><name>filter_customerid</name>
      <args>
        <disp>view</disp>
        <elements type="array">' . WuiXml::encode($customers) . '</elements>
        <default type="encoded">' . urlencode(isset($eventData['filter_customerid']) ? $eventData['filter_customerid'] : '') . '</default>
      </args>
    </combobox>

    <label row="2" col="0">
      <args>
        <label type="encoded">' . urlencode($gLocale->getStr('filter_account_manager.label')) . '</label>
      </args>
    </label>
    <combobox row="2" col="1"><name>filter_account_manager</name>
      <args>
        <disp>view</disp>
        <elements type="array">' . WuiXml::encode($gUsers) . '</elements>
        <default type="encoded">' . urlencode(isset($eventData['filter_account_manager']) ? $eventData['filter_account_manager'] : '') . '</default>
      </args>
    </combobox>

    <label row="3" col="0"><name>status</name>
      <args>
        <label type="encoded">' . urlencode($gLocale->getStr('filter_status.label')) . '</label>
      </args>
    </label>
    <combobox row="3" col="1"><name>filter_status</name>
      <args>
        <disp>view</disp>
        <elements type="array">' . WuiXml::encode($statuses) . '</elements>
        <default type="encoded">' . urlencode(isset($eventData['filter_status']) ? $eventData['filter_status'] : '') . '</default>
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
        <label type="encoded">' . urlencode($gLocale->getStr('invoices.label')) . '</label>
      </args>
    </label>';

    if ($search_results) {
        $gXml_def .= '    <table><name>invoices</name>
      <args>
        <headers type="array">' . WuiXml::encode($headers) . '</headers>
        <rowsperpage>10</rowsperpage>
        <pagesactionfunction>invoices_list_action_builder</pagesactionfunction>
        <pagenumber>' . (isset($eventData['pagenumber']) ? $eventData['pagenumber'] : '') . '</pagenumber>
        <sortby>' . $sort_by . '</sortby>
        <sortdirection>' . $sort_order . '</sortdirection>
        <rows>' . $num_invoices . '</rows>
      </args>
      <children>';

        $row = 0;
        $credit = 0;
        $due_credit = 0;
        $invoices_amount = 0;

        $page = 1;

        if (isset($eventData['pagenumber'])) {
            $page = $eventData['pagenumber'];
        } else {
            $table = new WuiTable('invoices');
            $page = $table->mPageNumber;
        }

        if ($page > ceil($num_invoices / 10)) {
            $page = ceil($num_invoices / 10);
        }

        $from = ($page * 10) - 10;
        $to = $from + 10 - 1;

        $xen_core = InnoworkCore::instance('innoworkcore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

        $summaries = $xen_core->getSummaries();

        while (list ($id, $fields) = each($search_results)) {
            $expired = false;
            // Due date

            $due_date_array = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->GetDateArrayFromTimestamp($fields['duedate']);
            $due_date = $locale_country->FormatShortArrayDate($due_date_array);

            if (($fields['total'] - $fields['paidamount']) > 0) {
                if ($due_date_array['year'] < date('Y') or ($due_date_array['year'] == date('Y') and $due_date_array['mon'] < date('m')) or ($due_date_array['year'] == date('Y') and $due_date_array['mon'] == date('m') and $due_date_array['mday'] < date('d'))) {
                    $expired = true;
                    // $due_date = '<font color="red">'.$due_date.'</font>';
                }
            }

            if ($eventData['filter_status'] == XENBILLING_FILTER_STATUS_ALL or ($eventData['filter_status'] == XENBILLING_FILTER_STATUS_PAID and ! $expired and (($fields['total'] - $fields['paidamount']) == 0)) or ($eventData['filter_status'] == XENBILLING_FILTER_STATUS_EXPIRED and $expired) or ($eventData['filter_status'] == XENBILLING_FILTER_STATUS_TOBEPAID and ! $expired and (($fields['total'] - $fields['paidamount']) != 0))) {
                if ($row >= $from and $row <= $to) {
                    $tmp_customer = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $fields['customerid']);
                    $tmp_customer_data = $tmp_customer->GetItem();

                    $tmp_project = new InnoworkProject(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $fields['projectid']);
                    $tmp_project_data = $tmp_project->GetItem();
                }

                // Credit

                $credit += $fields['total'] - $fields['paidamount'];
                if ($expired) {
                    $due_credit += $fields['total'] - $fields['paidamount'];
                }

                $invoices_amount += $fields['amount'];

                if ($row >= $from and $row <= $to) {
                    $gXml_def .= '<label row="' . $row . '" col="0"><name>number</name>
  <args>
    <label type="encoded">' . urlencode($fields['number']) . '</label>
  </args>
</label>
<label row="' . $row . '" col="1"><name>emissiondate</name>
  <args>
    <label type="encoded">' . urlencode($locale_country->FormatShortArrayDate(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                        ->getDataAccess()
                        ->GetDateArrayFromTimestamp($fields['emissiondate']))) . '</label>
  </args>
</label>
<vertgroup row="' . $row . '" col="2"><name>customer</name>
  <children>

<link><name>customer</name>
  <args>
    <link type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString($summaries['directorycompany']['domainpanel'], array(
                        array(
                            $summaries['directorycompany']['showdispatcher'],
                            $summaries['directorycompany']['showevent'],
                            array(
                                'id' => $fields['customerid']
                            )
                        )
                    ))) . '</link>
      <compact>true</compact>
    <label type="encoded">' . urlencode('<strong>' . $tmp_customer_data['companyname'] . '</strong>') . '</label>
  </args>
</link>

<link><name>project</name>
  <args>
    <link type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString($summaries['project']['domainpanel'], array(
                        array(
                            $summaries['project']['showdispatcher'],
                            $summaries['project']['showevent'],
                            array(
                                'id' => $fields['projectid']
                            )
                        )
                    ))) . '</link>
      <compact>true</compact>
    <label type="encoded">' . urlencode($tmp_project_data['name']) . '</label>
  </args>
</link>

  </children>
</vertgroup>
<label row="' . $row . '" col="3" halign="right"><name>total</name>
  <args>
    <label type="encoded">' . urlencode(number_format($fields['total'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator())) . '</label>
  </args>
</label>
<label row="' . $row . '" col="4"><name>duedate</name>
  <args>
    <label type="encoded">' . urlencode($due_date) . '</label>
  </args>
</label>
<label row="' . $row . '" col="5" halign="right"><name>paidamount</name>
  <args>
    <label type="encoded">' . urlencode(number_format($fields['paidamount'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator())) . '</label>
  </args>
</label>
<label row="' . $row . '" col="6" halign="right"><name>diff</name>
  <args>
    <label type="encoded">' . urlencode(($expired ? '<font color="red">' : '') . number_format($fields['total'] - $fields['paidamount'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator()) . ($expired ? '</font>' : '')) . '</label>
    <bold>' . ($expired ? 'true' : 'false') . '</bold>
  </args>
</label>
<innomatictoolbar row="' . $row . '" col="7"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">' . WuiXml::encode(array(
                        'view' => array(
                            'show' => array(
                                'label' => $gLocale->getStr('showinvoice.button'),
                                'themeimage' => 'zoom',
                                'horiz' => 'true',
                                'action' => WuiEventsCall::buildEventsCallString('', array(
                                    array(
                                        'view',
                                        'showinvoice',
                                        array(
                                            'id' => $id
                                        )
                                    )
                                ))
                            ),
                            'print' => array(
                                'label' => $gLocale->getStr('printinvoice.submit'),
                                'themeimage' => 'printer',
                                'horiz' => 'true',
                                'target' => '_blank',
                                'action' => WuiEventsCall::buildEventsCallString('', array(
                                    array(
                                        'view',
                                        'printinvoice',
                                        array(
                                            'id' => $id
                                        )
                                    )
                                ))
                            ),
                            'send' => array(
                                'label' => $gLocale->getStr('sendinvoice.button'),
                                'themeimage' => 'mail',
                                'horiz' => 'true',
                                'action' => WuiEventsCall::buildEventsCallString('', array(
                                    array(
                                        'view',
                                        'sendinvoice',
                                        array(
                                            'id' => $id
                                        )
                                    )
                                ))
                            ),
                            'payment' => array(
                                'label' => $gLocale->getStr('invoicepayment.button'),
                                'themeimage' => 'folder',
                                'horiz' => 'true',
                                'action' => WuiEventsCall::buildEventsCallString('', array(
                                    array(
                                        'view',
                                        'invoicepayment',
                                        array(
                                            'id' => $id
                                        )
                                    )
                                ))
                            ),
                            'remove' => array(
                                'label' => $gLocale->getStr('removeinvoice.button'),
                                'themeimage' => 'trash',
                                'horiz' => 'true',
                                'needconfirm' => 'true',
                                'confirmmessage' => $gLocale->getStr('removeinvoice.confirm'),
                                'action' => WuiEventsCall::buildEventsCallString('', array(
                                    array(
                                        'view',
                                        'default',
                                        ''
                                    ),
                                    array(
                                        'action',
                                        'removeinvoice',
                                        array(
                                            'id' => $id
                                        )
                                    )
                                ))
                            )
                        )
                    )) . '</toolbars>
  </args>
</innomatictoolbar>';
                }

                $row ++;
            }
        }

        $gXml_def .= '      </children>
    </table>

    <horizbar/>';
    } else {
        $gPage_status = $gLocale->getStr('noinvoices.status');
    }

    $gXml_def .= '    <grid>
      <children>

        <label row="0" col="0">
          <args>
            <label type="encoded">' . urlencode($gLocale->getStr('total_invoices.label')) . '</label>
          </args>
        </label>
        <string row="0" col="1">
          <args>
            <readonly>true</readonly>
            <value type="encoded">' . urlencode(number_format($invoices_amount, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator())) . '</value>
          </args>
        </string>

        <label row="1" col="0">
          <args>
            <label type="encoded">' . urlencode($gLocale->getStr('total_credit.label')) . '</label>
          </args>
        </label>
        <string row="1" col="1">
          <args>
            <readonly>true</readonly>
            <value type="encoded">' . urlencode(number_format($credit, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator())) . '</value>
          </args>
        </string>

        <label row="2" col="0">
          <args>
            <label type="encoded">' . urlencode($gLocale->getStr('total_expired_credit.label')) . '</label>
          </args>
        </label>
        <string row="2" col="1">
          <args>
            <readonly>true</readonly>
            <value type="encoded">' . urlencode(number_format($due_credit, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator())) . '</value>
          </args>
        </string>

      </children>
    </grid>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent('newinvoice', 'main_newinvoice');

function main_newinvoice($eventData)
{
    global $gXml_def, $gLocale, $gPage_title;

    // Companies list

    $xen_companies = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $search_results = $xen_companies->Search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId());

    $companies['0'] = $gLocale->getStr('nocompany.label');

    while (list ($id, $fields) = each($search_results)) {
        if ($fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_CUSTOMER or $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_BOTH)
            $companies[$id] = $fields['companyname'];
    }

    // Projects list

    $xen_projects = new InnoworkProject(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $search_results = $xen_projects->Search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId());

    $projects['0'] = $gLocale->getStr('noproject.label');

    while (list ($id, $fields) = each($search_results)) {
        $projects[$id] = $fields['name'];
    }

    $payments_query = &\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT * ' . 'FROM innowork_billing_payments ' . 'ORDER BY description');

    $payments['0'] = $gLocale->getStr('nopayment.label');

    while (! $payments_query->eof) {
        $payments[$payments_query->getFields('id')] = $payments_query->getFields('description');
        $payments_query->MoveNext();
    }

    // Invoice number

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

    $invoice_number = (int) $xen_invoice->GetLastInvoiceNumber();
    $invoice_number ++;

    // Emission date

    $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

    $curr_date = $locale_country->GetDateArrayFromSafeTimestamp($locale_country->SafeFormatTimestamp());

    // Defaults

    $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();

    $gXml_def .= '<vertgroup><name>newinvoice</name>
  <children>

    <table><name>invoice</name>
      <args>
        <headers type="array">' . WuiXml::encode(array(
        '0' => array(
            'label' => $gLocale->getStr('newinvoice.label')
        )
    )) . '</headers>
      </args>
      <children>

    <form row="0" col="0"><name>invoice</name>
      <args>
        <method>post</method>
        <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'showinvoice',
            ''
        ),
        array(
            'action',
            'newinvoice',
            ''
        )
    ))) . '</action>
      </args>
      <children>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>company</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('customer.label')) . '</label>
              </args>
            </label>
            <combobox><name>customerid</name>
              <args>
                <disp>action</disp>
                <elements type="array">' . WuiXml::encode($companies) . '</elements>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizbar/>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>number</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('number.label')) . '</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <disp>action</disp>
                <size>6</size>
                <value type="encoded">' . urlencode($invoice_number) . '</value>
              </args>
            </string>

            <label><name>emissiondate</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('emissiondate.label')) . '</label>
              </args>
            </label>
            <date><name>emissiondate</name>
              <args>
                <disp>action</disp>
                <value type="array">' . WuiXml::encode($curr_date) . '</value>
              </args>
            </date>

          </children>
        </horizgroup>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>payment</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('payment.label')) . '</label>
              </args>
            </label>
            <combobox><name>paymentid</name>
              <args>
                <disp>action</disp>
                <elements type="array">' . WuiXml::encode($payments) . '</elements>
                <default>' . $sets->GetDefaultPayment() . '</default>
              </args>
            </combobox>

            <!--
            <label><name>duedate</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('duedate.label')) . '</label>
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
            <label type="encoded">' . urlencode($gLocale->getStr('newinvoice.submit')) . '</label>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'showinvoice',
            ''
        ),
        array(
            'action',
            'newinvoice',
            ''
        )
    ))) . '</action>
          </args>
        </button>

      </children>
    </table>
  </children>
</vertgroup>';
}

$gMain_disp->AddEvent('showinvoice', 'main_showinvoice');

function main_showinvoice($eventData)
{
    global $gXml_def, $gLocale, $gPage_title;

    $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry());

    if (isset($GLOBALS['gEnv']['runtime']['xen-billing']['newinvoiceid'])) {
        $eventData['id'] = $GLOBALS['gEnv']['runtime']['xen-billing']['newinvoiceid'];
    }

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $inv_data = $xen_invoice->GetItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId());
    // Companies list

    $xen_customer = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $inv_data['customerid']);
    $search_results = $xen_customer->Search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId());

    $cust_data = $xen_customer->GetItem();

    $companies['0'] = $gLocale->getStr('nocompany.label');

    while (list ($id, $fields) = each($search_results)) {
        if ($fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_CUSTOMER or $fields['companytype'] == INNOWORKDIRECTORY_COMPANY_TYPE_BOTH)
            $companies[$id] = $fields['companyname'];
    }

    // Projects list

    $xen_projects = new InnoworkProject(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $search_results = $xen_projects->Search(array(
        'customerid' => $inv_data['customerid']
    ), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()
        ->getUserId());

    $projects['0'] = $gLocale->getStr('noproject.label');

    while (list ($id, $fields) = each($search_results)) {
        $projects[$id] = $fields['name'];
    }

    // Account managers

    $users_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT username,lname,fname ' . 'FROM domain_users ' . 'ORDER BY lname,fname');

    $gUsers[''] = $gLocale->getStr('no_account_manager.label');

    while (! $users_query->eof) {
        $gUsers[$users_query->getFields('username')] = $users_query->getFields('lname') . ' ' . $users_query->getFields('fname');
        $users_query->MoveNext();
    }

    $users_query->Free();

    // Payments

    $payments_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT * ' . 'FROM innowork_billing_payments ' . 'ORDER BY description');

    $payments['0'] = $gLocale->getStr('nopayment.label');

    while (! $payments_query->eof) {
        $payments[$payments_query->getFields('id')] = $payments_query->getFields('description');
        $payments_query->MoveNext();
    }

    // Due date

    $rows_headers[0]['label'] = $gLocale->getStr('row_description.header');
    $rows_headers[1]['label'] = $gLocale->getStr('row_amount.header');
    $rows_headers[2]['label'] = $gLocale->getStr('row_quantiy.header');
    $rows_headers[3]['label'] = $gLocale->getStr('row_discount.header');
    $rows_headers[4]['label'] = $gLocale->getStr('row_vat.header');
    $rows_headers[5]['label'] = $gLocale->getStr('row_total.header');

    $gXml_def .= '<vertgroup><name>showinvoice</name>
  <children>

    <table><name>invoice</name>
      <args>
        <headers type="array">' . WuiXml::encode(array(
        '0' => array(
            'label' => $gLocale->getStr('showinvoice.label')
        )
    )) . '</headers>
      </args>
      <children>

    <form row="0" col="0"><name>invoice</name>
      <args>
        <method>post</method>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'showinvoice',
            array(
                'id' => $eventData['id']
            )
        ),
        array(
            'action',
            'editinvoice',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
      </args>
      <children>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>company</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('customer.label')) . '</label>
              </args>
            </label>
            <combobox><name>customerid</name>
              <args>
                <disp>action</disp>
                <elements type="array">' . WuiXml::encode($companies) . '</elements>
                <default>' . $inv_data['customerid'] . '</default>
              </args>
            </combobox>

            <label><name>project</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('project.label')) . '</label>
              </args>
            </label>
            <combobox><name>projectid</name>
              <args>
                <disp>action</disp>
                <elements type="array">' . WuiXml::encode($projects) . '</elements>
                <default>' . $inv_data['projectid'] . '</default>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizgroup>
          <children>

            <label>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('account_manager.label')) . '</label>
              </args>
            </label>
            <combobox><name>accountmanager</name>
              <args>
                <disp>action</disp>
                <elements type="array">' . WuiXml::encode($gUsers) . '</elements>
                <default>' . $inv_data['accountmanager'] . '</default>
              </args>
            </combobox>

          </children>
        </horizgroup>

        <horizbar/>

        <horizgroup>
          <children>

            <label><name>street</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('street.label')) . '</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <readonly>true</readonly>
                <size>25</size>
                <value type="encoded">' . urlencode($cust_data['street']) . '</value>
              </args>
            </string>

            <label><name>city</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('city.label')) . '</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <readonly>true</readonly>
                <size>15</size>
                <value type="encoded">' . urlencode($cust_data['city']) . '</value>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizgroup>
          <children>

            <label><name>zip</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('zip.label')) . '</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <readonly>true</readonly>
                <size>5</size>
                <value type="encoded">' . urlencode($cust_data['zip']) . '</value>
              </args>
            </string>

            <label><name>state</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('state.label')) . '</label>
              </args>
            </label>
            <string><name>state</name>
              <args>
                <readonly>true</readonly>
                <size>2</size>
                <value type="encoded">' . urlencode($cust_data['state']) . '</value>
              </args>
            </string>

            <label><name>country</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('country.label')) . '</label>
              </args>
            </label>
            <string><name>country</name>
              <args>
                <readonly>true</readonly>
                <size>15</size>
                <value type="encoded">' . urlencode($cust_data['country']) . '</value>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizgroup>
          <children>

            <label><name>fiscalcode</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('fiscalcode.label')) . '</label>
              </args>
            </label>
            <string><name>fiscalcode</name>
              <args>
                <readonly>true</readonly>
                <size>14</size>
                <value type="encoded">' . urlencode($cust_data['fiscalcode']) . '</value>
              </args>
            </string>

          </children>
        </horizgroup>

        <horizbar/>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>number</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('number.label')) . '</label>
              </args>
            </label>
            <string><name>number</name>
              <args>
                <disp>action</disp>
                <size>6</size>
                <value type="encoded">' . urlencode($inv_data['number']) . '</value>
              </args>
            </string>

            <label><name>emissiondate</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('emissiondate.label')) . '</label>
              </args>
            </label>
            <date><name>emissiondate</name>
              <args>
                <disp>action</disp>
                <value type="array">' . WuiXml::encode(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->GetDateArrayFromTimestamp($inv_data['emissiondate'])) . '</value>
                <type>date</type>
              </args>
            </date>

          </children>
        </horizgroup>

        <horizgroup><name>invoice</name>
          <children>

            <label><name>payment</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('payment.label')) . '</label>
              </args>
            </label>
            <combobox><name>paymentid</name>
              <args>
                <disp>action</disp>
                <elements type="array">' . WuiXml::encode($payments) . '</elements>
                <default>' . $inv_data['paymentid'] . '</default>
              </args>
            </combobox>

            <label><name>duedate</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('duedate.label')) . '</label>
              </args>
            </label>
            <date><name>duedate</name>
              <args>
                <disp>action</disp>
                <value type="array">' . WuiXml::encode(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->GetDateArrayFromTimestamp($inv_data['duedate'])) . '</value>
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
                <label type="encoded">' . urlencode($gLocale->getStr('amount.label')) . '</label>
              </args>
            </label>
            <string><name>vat</name>
              <args>
                <size>16</size>
                <readonly>true</readonly>
                <value>' . WuiXml::cdata($locale_country->formatMoney($inv_data['amount'])) . '</value>
              </args>
            </string>

            <label><name>vat</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('vat.label')) . '</label>
              </args>
            </label>
            <string><name>vat</name>
              <args>
                <size>16</size>
                <readonly>true</readonly>
                <value>' . WuiXml::cdata($locale_country->formatMoney($inv_data['vat'])) . '</value>
              </args>
            </string>

            <label><name>total</name>
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('total.label')) . '</label>
              </args>
            </label>
            <string><name>total</name>
              <args>
                <size>16</size>
                <readonly>true</readonly>
                <value>' . WuiXml::cdata($locale_country->formatMoney($inv_data['total'])) . '</value>
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
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'showinvoice',
            array(
                'id' => $eventData['id']
            )
        ),
        array(
            'action',
            'editinvoice',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('updateinvoice.submit')) . '</label>
            <formsubmit>invoice</formsubmit>
          </args>
        </button>

        <button><name>print</name>
          <args>
            <themeimage>printer</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <target>_blank</target>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'printinvoice',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('printinvoice.submit')) . '</label>
          </args>
        </button>

        <button><name>send</name>
          <args>
            <themeimage>mail</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'sendinvoice',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('sendinvoice.button')) . '</label>
          </args>
        </button>

        <button><name>remove</name>
          <args>
            <themeimage>trash</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <needconfirm>true</needconfirm>
            <confirmmessage type="encoded">' . urlencode($gLocale->getStr('removeinvoice.confirm')) . '</confirmmessage>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default'
        ),
        array(
            'action',
            'removeinvoice',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('removeinvoice.submit')) . '</label>
          </args>
        </button>

          </children>
        </horizgroup>

        <vertgroup row="2" col="0">
          <children>

        <form><name>rows</name>
          <args>
            <method>post</method>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'showinvoice',
            array(
                'id' => $eventData['id']
            )
        ),
        array(
            'action',
            'editrows',
            array(
                'invoiceid' => $eventData['id']
            )
        )
    ))) . '</action>
          </args>
          <children>

            <table><name>rows</name>
              <args>
                <headers type="array">' . WuiXml::encode($rows_headers) . '</headers>
              </args>
              <children>';

    $row_list = $xen_invoice->getRows();

    $vats_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->execute('SELECT * FROM innowork_billing_vat_codes ORDER BY vat');

    $vats['0'] = $gLocale->getStr('novat.label');
    $vats_perc = array();

    while (! $vats_query->eof) {
        $vats[$vats_query->getFields('id')] = $vats_query->getFields('vat');
        $vats_perc[$vats_query->getFields('id')] = $vats_query->getFields('percentual');
        $vats_query->moveNext();
    }

    $row = 0;
    unset($row_list['amount']);
    foreach ($row_list as $row_data) {
        $gXml_def .= '<string row="' . $row . '" col="0"><name>description' . $row_data['id'] . '</name>
  <args>
    <disp>action</disp>
    <size>40</size>
    <value type="encoded">' . urlencode($row_data['description']) . '</value>
  </args>
</string>
<string row="' . $row . '" col="1"><name>amount' . $row_data['id'] . '</name>
  <args>
    <disp>action</disp>
    <size>10</size>
    <value type="encoded">' . urlencode($row_data['unf_amount']) . '</value>
  </args>
</string>
<string row="' . $row . '" col="2"><name>quantity' . $row_data['id'] . '</name>
  <args>
    <disp>action</disp>
    <size>4</size>
    <value type="encoded">' . urlencode($row_data['quantity']) . '</value>
  </args>
</string>
<string row="' . $row . '" col="3"><name>discount' . $row_data['id'] . '</name>
  <args>
    <disp>action</disp>
    <size>4</size>
    <value type="encoded">' . urlencode($row_data['discount']) . '</value>
  </args>
</string>
<combobox row="' . $row . '" col="4"><name>vatid' . $row_data['id'] . '</name>
  <args>
    <disp>action</disp>
    <elements type="array">' . WuiXml::encode($vats) . '</elements>
    <default>' . $row_data['vatid'] . '</default>
  </args>
</combobox>
<label row="' . $row . '" col="5" halign="right">
  <args>
   <label>' . WuiXml::cdata($locale_country->formatMoney($row_data['total'])) . '</label>
  </args>
</label>
<innomatictoolbar row="' . $row . '" col="6"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">' . WuiXml::encode(array(
            'main' => array(
                'update' => array(
                    'label' => $gLocale->getStr('update_row.button'),
                    'themeimage' => 'buttonok',
                    'horiz' => 'true',
                    'formsubmit' => 'rows',
                    'action' => WuiEventsCall::buildEventsCallString('', array(
                        array(
                            'view',
                            'showinvoice',
                            array(
                                'id' => $eventData['id']
                            )
                        ),
                        array(
                            'action',
                            'editrows',
                            array(
                                'invoiceid' => $eventData['id']
                            )
                        )
                    ))
                ),
                'remove' => array(
                    'label' => $gLocale->getStr('remove_row.button'),
                    'themeimage' => 'mathsub',
                    'horiz' => 'true',
                    'needconfirm' => 'true',
                    'confirmmessage' => $gLocale->getStr('remove_row.confirm'),
                    'action' => WuiEventsCall::buildEventsCallString('', array(
                        array(
                            'view',
                            'showinvoice',
                            array(
                                'id' => $eventData['id']
                            )
                        ),
                        array(
                            'action',
                            'removerow',
                            array(
                                'invoiceid' => $eventData['id'],
                                'rowid' => $row_data['id']
                            )
                        )
                    ))
                )
            )
        )) . '</toolbars>
  </args>
</innomatictoolbar>';

        $row ++;
    }

    $gXml_def .= '              </children>
            </table>

              </children>
            </form>';

    unset($rows_headers);

    $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
    $rows_headers[0]['label'] = $gLocale->getStr('row_description.header');
    $rows_headers[1]['label'] = $gLocale->getStr('row_amount.header');
    $rows_headers[2]['label'] = $gLocale->getStr('row_quantiy.header');
    $rows_headers[3]['label'] = $gLocale->getStr('row_discount.header');
    $rows_headers[4]['label'] = $gLocale->getStr('row_vat.header');

    $gXml_def .= '<horizbar/>

<form><name>addrow</name>
          <args>
            <method>post</method>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'showinvoice',
            array(
                'id' => $eventData['id']
            )
        ),
        array(
            'action',
            'addrow',
            array(
                'invoiceid' => $eventData['id']
            )
        )
    ))) . '</action>
          </args>
  <children>
            <table><name>rows</name>
              <args>
                <headers type="array">' . WuiXml::encode($rows_headers) . '</headers>
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
    <elements type="array">' . WuiXml::encode($vats) . '</elements>
    <default>' . $sets->GetDefaultVat() . '</default>
  </args>
</combobox>
<innomatictoolbar row="0" col="5"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">' . WuiXml::encode(array(
        'main' => array(
            'add' => array(
                'label' => $gLocale->getStr('add_row.button'),
                'themeimage' => 'mathadd',
                'horiz' => 'true',
                'formsubmit' => 'addrow',
                'action' => WuiEventsCall::buildEventsCallString('', array(
                    array(
                        'view',
                        'showinvoice',
                        array(
                            'id' => $eventData['id']
                        )
                    ),
                    array(
                        'action',
                        'addrow',
                        array(
                            'invoiceid' => $eventData['id']
                        )
                    )
                ))
            )
        )
    )) . '</toolbars>
  </args>
</innomatictoolbar>
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

$gMain_disp->AddEvent('invoicepayment', 'main_invoicepayment');

function main_invoicepayment($eventData)
{
    global $gLocale, $gXml_def;

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $inv_data = $xen_invoice->GetItem();

    $gXml_def = '<vertgroup>
  <children>

    <form><name>paidamount</name>
      <args>
        <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            ''
        ),
        array(
            'action',
            'invoicepayment',
            array(
                'invoiceid' => $eventData['id']
            )
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

          <label row="0" col="0">
            <args>
              <label type="encoded">' . urlencode($gLocale->getStr('invoice_amount.label')) . '</label>
            </args>
          </label>
          <string row="0" col="1">
            <args>
              <readonly>true</readonly>
              <size>10</size>
              <disp>action</disp>
              <value type="encoded">' . urlencode($inv_data['total']) . '</value>
            </args>
          </string>

          <label row="1" col="0">
            <args>
              <label type="encoded">' . urlencode($gLocale->getStr('paid_amount.label')) . '</label>
            </args>
          </label>
          <string row="1" col="1"><name>paidamount</name>
            <args>
              <size>10</size>
              <disp>action</disp>
              <value type="encoded">' . urlencode($inv_data['paidamount']) . '</value>
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
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            ''
        ),
        array(
            'action',
            'invoicepayment',
            array(
                'invoiceid' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('payment.submit')) . '</label>
            <formsubmit>paidamount</formsubmit>
          </args>

    </button>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent('sendinvoice', 'main_sendinvoice');

function main_sendinvoice($eventData)
{
    global $gLocale, $gXml_def;

    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $inv_data = $xen_invoice->GetItem();

    require_once ('innowork/groupware/InnoworkCompany.php');

    $xen_customer = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $inv_data['customerid']);

    $cust_data = $xen_customer->GetItem();

    $gXml_def = '<vertgroup>
  <children>

    <form><name>email</name>
      <args>
        <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            ''
        ),
        array(
            'action',
            'sendinvoice',
            array(
                'invoiceid' => $eventData['id']
            )
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

          <label row="0" col="0">
            <args>
              <label type="encoded">' . urlencode($gLocale->getStr('dest_email.label')) . '</label>
            </args>
          </label>
          <string row="0" col="1"><name>email</name>
            <args>
              <disp>action</disp>
              <size>25</size>
              <value type="encoded">' . urlencode($cust_data['email']) . '</value>
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
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            ''
        ),
        array(
            'action',
            'sendinvoice',
            array(
                'invoiceid' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('send.submit')) . '</label>
            <formsubmit>email</formsubmit>
          </args>

    </button>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent('printinvoice', 'main_printinvoice');

function main_printinvoice($eventData)
{
    $xen_invoice = new \Innowork\Billing\InnoworkInvoice(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),

    $eventData['id']);

    echo $xen_invoice->CreateHtmlInvoice();

    exit();
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gWui->AddChild(new WuiInnomaticPage('page', array(
    'pagetitle' => $gPage_title,
    'icon' => 'document',
    'toolbars' => array(
        new WuiInnomaticToolbar('main', array(
            'toolbars' => $gToolbars,
            'toolbar' => 'true'
        )),
        new WuiInnomaticToolbar('core', array(
            'toolbars' => $gCore_toolbars,
            'toolbar' => 'true'
        ))
    ),
    'maincontent' => new WuiXml('page', array(
        'definition' => $gXml_def
    )),
    'status' => $gPage_status
)));

$gWui->Render();
