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
    'xenbilling_site_prefs',
    $gEnv['user']['locale']['language']
    );

$gHui = new Hui( $gEnv['root']['db'] );
$gHui->LoadWidget( 'xml' );
$gHui->LoadWidget( 'amppage' );
$gHui->LoadWidget( 'amptoolbar' );

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->GetStr( 'preferences.title' );
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
        )
    );

$gToolbars['vats'] = array(
    'vats' => array(
        'label' => $gLocale->GetStr( 'vats.toolbar' ),
        'themeimage' => 'view_icon',
        'horiz' => 'true',
        'action' => build_events_call_string( '', array( array(
            'main',
            'default',
            '' ) ) )
        ),
    'newvat' => array(
        'label' => $gLocale->GetStr( 'newvat.toolbar' ),
        'themeimage' => 'filenew',
        'horiz' => 'true',
        'action' => build_events_call_string( '', array( array(
            'main',
            'newvat',
            '' ) ) )
        )
    );
$gToolbars['payments'] = array(
    'payments' => array(
        'label' => $gLocale->GetStr( 'payments.toolbar' ),
        'themeimage' => 'view_icon',
        'horiz' => 'true',
        'action' => build_events_call_string( '', array( array(
            'main',
            'payments',
            '' ) ) )
        ),
    'newpayment' => array(
        'label' => $gLocale->GetStr( 'newpayment.toolbar' ),
        'themeimage' => 'filenew',
        'horiz' => 'true',
        'action' => build_events_call_string( '', array( array(
            'main',
            'newpayment',
            '' ) ) )
        )
    );
$gToolbars['settings'] = array(
    'settings' => array(
        'label' => $gLocale->GetStr( 'settings.toolbar' ),
        'themeimage' => 'configure',
        'horiz' => 'true',
        'action' => build_events_call_string( '', array( array(
            'main',
            'settings',
            '' ) ) )
        )
    );

// ----- Action dispatcher -----
//
$gAction_disp = new HuiDispatcher( 'action' );

// Vats

$gAction_disp->AddEvent(
    'newvat',
    'action_newvat'
    );
function action_newvat(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $xen_vat = new XenBilling_Vat();
    if ( $xen_vat->Create(
        $eventData['vat'],
        $eventData['percentual']
        ) )
    {
        $gPage_status = $gLocale->GetStr( 'vat_added.status' );
    }
}

$gAction_disp->AddEvent(
    'editvat',
    'action_editvat'
    );
function action_editvat(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $xen_vat = new XenBilling_Vat(
        $eventData['id']
        );

    $xen_vat->SetDescription( $eventData['vat'] );
    $xen_vat->SetPercentual( $eventData['percentual'] );

    $gPage_status = $gLocale->GetStr( 'vat_updated.status' );
}

$gAction_disp->AddEvent(
    'removevat',
    'action_removevat'
    );
function action_removevat(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $xen_vat = new XenBilling_Vat(
        $eventData['id']
        );
    if ( $xen_vat->Remove() )
    {
        $gPage_status = $gLocale->GetStr( 'vat_removed.status' );
    }
}

// Payments

$gAction_disp->AddEvent(
    'newpayment',
    'action_newpayment'
    );
function action_newpayment(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $xen_payment = new XenBilling_Payment();
    if ( $xen_payment->Create(
        $eventData['description'],
        $eventData['days'],
        isset( $eventData['monthend'] ) and $eventData['monthend'] == 'on' ? true : false
        ) )
    {
        $gPage_status = $gLocale->GetStr( 'payment_added.status' );
    }
}

$gAction_disp->AddEvent(
    'editpayment',
    'action_editpayment'
    );
function action_editpayment(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $xen_payment = new XenBilling_Payment(
        $eventData['id']
        );

    $xen_payment->SetDescription( $eventData['description'] );
    $xen_payment->SetDays( $eventData['days'] );
    $xen_payment->SetMonthEnd( isset( $eventData['monthend'] ) and $eventData['monthend'] == 'on' ? true : false );

    $gPage_status = $gLocale->GetStr( 'payment_updated.status' );
}

$gAction_disp->AddEvent(
    'removepayment',
    'action_removepayment'
    );
function action_removepayment(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $xen_payment = new XenBilling_Payment(
        $eventData['id']
        );
    if ( $xen_payment->Remove() )
    {
        $gPage_status = $gLocale->GetStr( 'payment_removed.status' );
    }
}

$gAction_disp->AddEvent(
    'setgeneral',
    'action_setgeneral'
    );
function action_setgeneral(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $sets = new XenBilling_SettingsHandler();
    $sets->SetEmail( $eventData['email'] );
    $sets->SetSmtpServer( $eventData['smtpserver'] );

    $gPage_status = $gLocale->GetStr( 'settings_set.status' );
}

$gAction_disp->AddEvent(
    'setdefaults',
    'action_setdefaults'
    );
function action_setdefaults(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $sets = new XenBilling_SettingsHandler();
    $sets->SetDefaultPayment( $eventData['paymentid'] );
    $sets->SetDefaultVat( $eventData['vatid'] );

    $gPage_status = $gLocale->GetStr( 'settings_set.status' );
}

$gAction_disp->AddEvent(
    'settemplates',
    'action_settemplates'
    );
function action_settemplates(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $sets = new XenBilling_SettingsHandler();

    if ( is_uploaded_file( $eventData['invoice_template']['tmp_name'] ) )
    {
        if ( $fh = fopen( $eventData['invoice_template']['tmp_name'], 'r' ) )
        {
            $sets->SetInvoiceTemplate( fread( $fh, filesize( $eventData['invoice_template']['tmp_name'] ) ) );

            fclose( $fh );
        }
    }

    $gPage_status = $gLocale->GetStr( 'settings_set.status' );
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new HuiDispatcher( 'main' );

// Vat

$gMain_disp->AddEvent(
    'default',
    'main_default' );
function main_default( $eventData )
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status;

    $vats_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT * '.
        'FROM innowork_billing_vat_codes '.
        'ORDER BY vat'
        );

    if ( $vats_query->NumRows() )
    {
        $headers[0]['label'] = $gLocale->GetStr( 'vat.header' );
        $headers[1]['label'] = $gLocale->GetStr( 'percentual.header' );

        $gXml_def =
'<table>
  <args>
    <headers type="array">'.huixml_encode( $headers ).'</headers>
  </args>
  <children>';

        $row = 0;

        while ( !$vats_query->eof )
        {
            $gXml_def .=
'<label row="'.$row.'" col="0">
  <args>
    <label type="encoded">'.urlencode( $vats_query->Fields( 'vat' ) ).'</label>
  </args>
</label>
<label row="'.$row.'" col="1">
  <args>
    <label type="encoded">'.urlencode( $vats_query->Fields( 'percentual' ) ).'</label>
  </args>
</label>
<amptoolbar row="'.$row.'" col="2">
  <args>
    <frame>false</frame>
    <toolbars type="array">'.huixml_encode( array(
        'main' => array(
            'edit' => array(
                'label' => $gLocale->GetStr( 'editvat.button' ),
                'themeimage' => 'pencil',
                'horiz' => 'true',
                'action' => build_events_call_string( '', array( array(
                    'main',
                    'editvat',
                    array( 'id' => $vats_query->Fields( 'id' ) ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->GetStr( 'removevat.button' ),
                'themeimage' => 'editdelete',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->GetStr( 'removevat.confirm' ),
                'action' => build_events_call_string( '', array(
                    array(
                        'main',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'removevat',
                        array( 'id' => $vats_query->Fields( 'id' ) ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</amptoolbar>';
            $vats_query->MoveNext();
            $row++;
        }

        $gXml_def .=
'  </children>
</table>';
    }
    else
    {
        $gPage_status = $gLocale->GetStr( 'novats.status' );
    }
}

$gMain_disp->AddEvent(
    'newvat',
    'main_newvat'
    );
function main_newvat(
    $eventData
    )
{
    global $gLocale, $gXml_def;

    $gXml_def =
'<vertgroup>
  <children>

    <form><name>newvat</name>
      <args>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'newvat',
                        '' )
                ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'vat.label' ) ).'</label>
              </args>
            </label>

            <string row="0" col="1"><name>vat</name>
              <args>
                <disp>action</disp>
                <size>15</size>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'percentual.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="1"><name>percentual</name>
              <args>
                <disp>action</disp>
                <size>5</size>
              </args>
            </string>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

        <button><name>apply</name>
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
                        'newvat',
                        '' )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'newvat.submit' ) ).'</label>
            <formsubmit>newvat</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent(
    'editvat',
    'main_editvat'
    );
function main_editvat(
    $eventData
    )
{
    global $gLocale, $gXml_def;

    $vat_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT * '.
        'FROM innowork_billing_vat_codes '.
        'WHERE id='.$eventData['id']
        );

    $gXml_def =
'<vertgroup>
  <children>

    <form><name>editvat</name>
      <args>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'editvat',
                        array( 'id' => $eventData['id'] ) )
                ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'vat.label' ) ).'</label>
              </args>
            </label>

            <string row="0" col="1"><name>vat</name>
              <args>
                <disp>action</disp>
                <size>15</size>
                <value type="encoded">'.urlencode( $vat_query->Fields( 'vat' ) ).'</value>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'percentual.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="1"><name>percentual</name>
              <args>
                <disp>action</disp>
                <size>5</size>
                <value type="encoded">'.urlencode( $vat_query->Fields( 'percentual' ) ).'</value>
              </args>
            </string>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

        <button><name>apply</name>
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
                        'editvat',
                        array( 'id' => $eventData['id'] ) )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'editvat.submit' ) ).'</label>
            <formsubmit>newvat</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

// Payments

$gMain_disp->AddEvent(
    'payments',
    'main_payments' );
function main_payments( $eventData )
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status;

    $payments_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT * '.
        'FROM innowork_billing_payments '.
        'ORDER BY description'
        );

    if ( $payments_query->NumRows() )
    {
        $headers[0]['label'] = $gLocale->GetStr( 'payment.header' );
        $headers[1]['label'] = $gLocale->GetStr( 'days.header' );
        $headers[2]['label'] = $gLocale->GetStr( 'monthend.header' );

        $gXml_def =
'<table>
  <args>
    <headers type="array">'.huixml_encode( $headers ).'</headers>
  </args>
  <children>';

        $row = 0;

        while ( !$payments_query->eof )
        {
            $gXml_def .=
'<label row="'.$row.'" col="0">
  <args>
    <label type="encoded">'.urlencode( $payments_query->Fields( 'description' ) ).'</label>
  </args>
</label>
<label row="'.$row.'" col="1">
  <args>
    <label type="encoded">'.urlencode( $payments_query->Fields( 'days' ) ).'</label>
  </args>
</label>
<label row="'.$row.'" col="2">
  <args>
    <label type="encoded">'.urlencode(
        $payments_query->Fields( 'monthend' ) == $GLOBALS['gEnv']['site']['db']->fmttrue ?
        $gLocale->GetStr( 'yes.label' ) :
        $gLocale->GetStr( 'no.label' )
        ).'</label>
  </args>
</label>
<amptoolbar row="'.$row.'" col="3">
  <args>
    <frame>false</frame>
    <toolbars type="array">'.huixml_encode( array(
        'main' => array(
            'edit' => array(
                'label' => $gLocale->GetStr( 'editpayment.button' ),
                'themeimage' => 'pencil',
                'horiz' => 'true',
                'action' => build_events_call_string( '', array( array(
                    'main',
                    'editpayment',
                    array( 'id' => $payments_query->Fields( 'id' ) ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->GetStr( 'removepayment.button' ),
                'themeimage' => 'editdelete',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->GetStr( 'removepayment.confirm' ),
                'action' => build_events_call_string( '', array(
                    array(
                        'main',
                        'payments',
                        ''
                    ),
                    array(
                        'action',
                        'removepayment',
                        array( 'id' => $payments_query->Fields( 'id' ) ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</amptoolbar>';
            $payments_query->MoveNext();
            $row++;
        }

        $gXml_def .=
'  </children>
</table>';
    }
    else
    {
        $gPage_status = $gLocale->GetStr( 'nopayments.status' );
    }
}

$gMain_disp->AddEvent(
    'newpayment',
    'main_newpayment'
    );
function main_newpayment(
    $eventData
    )
{
    global $gLocale, $gXml_def;

    $gXml_def =
'<vertgroup>
  <children>

    <form><name>newpayment</name>
      <args>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'payments',
                        ''
                        ),
                    array(
                        'action',
                        'newpayment',
                        '' )
                ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'payment.label' ) ).'</label>
              </args>
            </label>

            <string row="0" col="1"><name>description</name>
              <args>
                <disp>action</disp>
                <size>25</size>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'days.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="1"><name>days</name>
              <args>
                <disp>action</disp>
                <size>5</size>
              </args>
            </string>

            <label row="2" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'monthend.label' ) ).'</label>
              </args>
            </label>

            <checkbox row="2" col="1"><name>monthend</name>
              <args>
                <disp>action</disp>
              </args>
            </checkbox>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

        <button><name>apply</name>
          <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'payments',
                        ''
                        ),
                    array(
                        'action',
                        'newpayment',
                        '' )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'newpayment.submit' ) ).'</label>
            <formsubmit>newpayment</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

$gMain_disp->AddEvent(
    'editpayment',
    'main_editpayment'
    );
function main_editpayment(
    $eventData
    )
{
    global $gLocale, $gXml_def;

    $payment_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT * '.
        'FROM innowork_billing_payments '.
        'WHERE id='.$eventData['id']
        );

    $gXml_def =
'<vertgroup>
  <children>

    <form><name>editpayment</name>
      <args>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'payments',
                        ''
                        ),
                    array(
                        'action',
                        'editpayment',
                        array( 'id' => $eventData['id'] ) )
                ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'payment.label' ) ).'</label>
              </args>
            </label>

            <string row="0" col="1"><name>description</name>
              <args>
                <disp>action</disp>
                <size>25</size>
                <value type="encoded">'.urlencode( $payment_query->Fields( 'description' ) ).'</value>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'days.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="1"><name>days</name>
              <args>
                <disp>action</disp>
                <size>5</size>
                <value type="encoded">'.urlencode( $payment_query->Fields( 'days' ) ).'</value>
              </args>
            </string>

            <label row="2" col="0">
              <args>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'monthend.label' ) ).'</label>
              </args>
            </label>

            <checkbox row="2" col="1"><name>monthend</name>
              <args>
                <disp>action</disp>
                <checked>'.(
                    $payment_query->Fields( 'monthend' ) == $GLOBALS['gEnv']['site']['db']->fmttrue ?
                    'true':
                    'false'
                    ).'</checked>
              </args>
            </checkbox>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

        <button><name>apply</name>
          <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( build_events_call_string( '', array(
                    array(
                        'main',
                        'payments',
                        ''
                        ),
                    array(
                        'action',
                        'editpayment',
                        array( 'id' => $eventData['id'] ) )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->GetStr( 'editpayment.submit' ) ).'</label>
            <formsubmit>editpayment</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

function settings_tab_action_builder( $tab )
{
    return build_events_call_string( '', array( array(
            'main',
            'settings',
            array(
                'tabpage' => $tab
                )
            )
        ) );
}

$gMain_disp->AddEvent(
    'settings',
    'main_settings'
    );
function main_settings(
    $eventData
    )
{
    global $gLocale, $gPage_title, $gXml_def;

    $tabs[0]['label'] = $gLocale->GetStr( 'general_settings.tab' );
    $tabs[1]['label'] = $gLocale->GetStr( 'defaults_settings.tab' );
    $tabs[2]['label'] = $gLocale->GetStr( 'templates_settings.tab' );

    $sets = new XenBilling_SettingsHandler();

    $vats_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT id,vat '.
        'FROM innowork_billing_vat_codes '.
        'ORDER BY vat'
        );

    $vats[0] = $gLocale->GetStr( 'novat.label' );
    while ( !$vats_query->eof )
    {
        $vats[$vats_query->Fields( 'id' )] = $vats_query->Fields( 'vat' );
        $vats_query->MoveNext();
    }

    $payments_query = &$GLOBALS['gEnv']['site']['db']->Execute(
        'SELECT id,description '.
        'FROM innowork_billing_payments '.
        'ORDER BY description'
        );

    $payments[0] = $gLocale->GetStr( 'nopayment.label' );
    while ( !$payments_query->eof )
    {
        $payments[$payments_query->Fields( 'id' )] = $payments_query->Fields( 'description' );
        $payments_query->MoveNext();
    }

    $gXml_def =
'<vertgroup>
  <children>

    <label>
      <args>
        <label type="encoded">'.urlencode( $gLocale->GetStr( 'settings.label' ) ).'</label>
        <bold>true</bold>
      </args>
    </label>

    <tab><name>settings</name>
      <args>
        <tabs type="array">'.huixml_encode( $tabs ).'</tabs>
        <activetab>'.( isset( $eventData['tabpage'] ) ? $eventData['tabpage'] : '' ).'</activetab>
        <tabactionfunction>settings_tab_action_builder</tabactionfunction>
      </args>
      <children>

        <vertgroup>
          <children>

            <form><name>settings</name>
              <args>
                <action type="encoded">'.urlencode( build_events_call_string( '', array(
                        array(
                            'main',
                            'settings',
                            ''
                            ),
                        array(
                            'action',
                            'setgeneral',
                            '' )
                    ) ) ).'</action>
              </args>
              <children>

                <grid>
                  <children>

                    <label row="0" col="0">
                      <args>
                        <label type="encoded">'.urlencode( $gLocale->GetStr( 'email.label' ) ).'</label>
                      </args>
                    </label>

                    <string row="0" col="1"><name>email</name>
                      <args>
                        <disp>action</disp>
                        <size>25</size>
                        <value type="encoded">'.urlencode( $sets->GetEmail() ).'</value>
                      </args>
                    </string>

                    <label row="1" col="0">
                      <args>
                        <label type="encoded">'.urlencode( $gLocale->GetStr( 'smtpserver.label' ) ).'</label>
                      </args>
                    </label>

                    <string row="1" col="1"><name>smtpserver</name>
                      <args>
                        <disp>action</disp>
                        <size>25</size>
                        <value type="encoded">'.urlencode( $sets->GetSmtpServer() ).'</value>
                      </args>
                    </string>

                  </children>
                </grid>

              </children>
            </form>

            <horizbar/>

            <button><name>apply</name>
              <args>
                <themeimage>buttonok</themeimage>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode( build_events_call_string( '', array(
                        array(
                            'main',
                            'settings',
                            ''
                            ),
                        array(
                            'action',
                            'setgeneral',
                            '' )
                    ) ) ).'</action>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'apply.submit' ) ).'</label>
                <formsubmit>settings</formsubmit>
              </args>
            </button>

          </children>
        </vertgroup>

        <vertgroup>
          <children>

            <form><name>settings</name>
              <args>
                <action type="encoded">'.urlencode( build_events_call_string( '', array(
                        array(
                            'main',
                            'settings',
                            ''
                            ),
                        array(
                            'action',
                            'setdefaults',
                            '' )
                    ) ) ).'</action>
              </args>
              <children>

                <grid>
                  <children>

                    <label row="0" col="0">
                      <args>
                        <label type="encoded">'.urlencode( $gLocale->GetStr( 'default_vat.label' ) ).'</label>
                      </args>
                    </label>

                    <combobox row="0" col="1"><name>vatid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.huixml_encode( $vats ).'</elements>
                        <default>'.$sets->GetDefaultVat().'</default>
                      </args>
                    </combobox>

                    <label row="1" col="0">
                      <args>
                        <label type="encoded">'.urlencode( $gLocale->GetStr( 'default_payment.label' ) ).'</label>
                      </args>
                    </label>

                    <combobox row="1" col="1"><name>paymentid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.huixml_encode( $payments ).'</elements>
                        <default>'.$sets->GetDefaultPayment().'</default>
                      </args>
                    </combobox>

                  </children>
                </grid>

              </children>
            </form>

            <horizbar/>

            <button><name>apply</name>
              <args>
                <themeimage>buttonok</themeimage>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode( build_events_call_string( '', array(
                        array(
                            'main',
                            'settings',
                            ''
                            ),
                        array(
                            'action',
                            'setdefaults',
                            '' )
                    ) ) ).'</action>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'apply.submit' ) ).'</label>
                <formsubmit>settings</formsubmit>
              </args>
            </button>

          </children>
        </vertgroup>

        <vertgroup>
          <children>

            <form><name>settings</name>
              <args>
                <action type="encoded">'.urlencode( build_events_call_string( '', array(
                        array(
                            'main',
                            'settings',
                            ''
                            ),
                        array(
                            'action',
                            'settemplates',
                            '' )
                    ) ) ).'</action>
              </args>
              <children>

                <grid>
                  <children>

                    <label row="0" col="0">
                      <args>
                        <label type="encoded">'.urlencode( $gLocale->GetStr( 'invoice_template.label' ) ).'</label>
                      </args>
                    </label>

                    <file row="0" col="1"><name>invoice_template</name>
                      <args>
                        <disp>action</disp>
                      </args>
                    </file>

                  </children>
                </grid>

              </children>
            </form>

            <horizbar/>

            <button><name>apply</name>
              <args>
                <themeimage>buttonok</themeimage>
                <horiz>true</horiz>
                <frame>false</frame>
                <action type="encoded">'.urlencode( build_events_call_string( '', array(
                        array(
                            'main',
                            'settings',
                            ''
                            ),
                        array(
                            'action',
                            'settemplates',
                            '' )
                    ) ) ).'</action>
                <label type="encoded">'.urlencode( $gLocale->GetStr( 'apply.submit' ) ).'</label>
                <formsubmit>settings</formsubmit>
              </args>
            </button>

          </children>
        </vertgroup>

      </children>
    </tab>

  </children>
</vertgroup>';

    $gPage_title = $gLocale->GetStr( 'settings.title' );
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gHui->AddChild( new HuiAmpPage( 'page', array(
    'pagetitle' => $gPage_title,
    'icon' => 'document',
    'toolbars' => array(
        new HuiAmpToolbar(
            'main',
            array(
                'toolbars' => $gToolbars
                ) ),
        new HuiAmpToolBar(
            'core',
            array(
                'toolbars' => $gCore_toolbars
                ) ),
            ),
    'maincontent' => new HuiXml(
        'page', array(
            'definition' => $gXml_def
            ) ),
    'status' => $gPage_status
    ) ) );

$gHui->Render();

?>
