<?php

require_once 'innomatic/wui/Wui.php';

global $gLocale, $gPage_title, $gXml_def, $gPage_status;

$gInnowork_core = \Innowork\Core\InnoworkCore::instance('\Innowork\Core\InnoworkCore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

$gLocale = new \Innomatic\Locale\LocaleCatalog('innowork-billing::prefs', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());

$gWui = Wui::instance('wui');
$gWui->loadAllWidgets();

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr('preferences.title');
$gCore_toolbars = $gInnowork_core->GetMainToolBar();
$gToolbars['invoices'] = array(
    'invoices' => array(
        'label' => $gLocale->getStr('invoices.toolbar'),
        'themeimage' => 'listbulletleft',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('innoworkbilling', array(
            array(
                'view',
                'default',
                ''
            )
        ))
    )
);

$gToolbars['vats'] = array(
    'vats' => array(
        'label' => $gLocale->getStr('vats.toolbar'),
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
    'newvat' => array(
        'label' => $gLocale->getStr('newvat.toolbar'),
        'themeimage' => 'mathadd',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('', array(
            array(
                'view',
                'newvat',
                ''
            )
        ))
    )
);
$gToolbars['payments'] = array(
    'payments' => array(
        'label' => $gLocale->getStr('payments.toolbar'),
        'themeimage' => 'listbulletleft',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('', array(
            array(
                'view',
                'payments',
                ''
            )
        ))
    ),
    'newpayment' => array(
        'label' => $gLocale->getStr('newpayment.toolbar'),
        'themeimage' => 'mathadd',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('', array(
            array(
                'view',
                'newpayment',
                ''
            )
        ))
    )
);
$gToolbars['banks'] = array(
    'banks' => array(
        'label' => $gLocale->getStr('banks.toolbar'),
        'themeimage' => 'listbulletleft',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('', array(
            array(
                'view',
                'banks',
                ''
            )
        ))
    ),
    'newbank' => array(
        'label' => $gLocale->getStr('newbank.toolbar'),
        'themeimage' => 'mathadd',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('', array(
            array(
                'view',
                'newbank',
                ''
            )
        ))
    )
);
$gToolbars['settings'] = array(
    'settings' => array(
        'label' => $gLocale->getStr('settings.toolbar'),
        'themeimage' => 'settings1',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString('', array(
            array(
                'view',
                'settings',
                ''
            )
        ))
    )
);

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher('action');

// Vats

$gAction_disp->addEvent('newvat', 'action_newvat');

function action_newvat($eventData)
{
    global $gPage_status, $gLocale;
    
    $xen_vat = new \Innowork\Billing\InnoworkBillingVat();
    if ($xen_vat->Create($eventData['vat'], $eventData['percentual'], $eventData['description'])) {
        $gPage_status = $gLocale->getStr('vat_added.status');
    }
}

$gAction_disp->addEvent('editvat', 'action_editvat');

function action_editvat($eventData)
{
    global $gPage_status, $gLocale;
    
    $xen_vat = new \Innowork\Billing\InnoworkBillingVat($eventData['id']);
    
    $xen_vat->setName($eventData['vat']);
    $xen_vat->setDescription($eventData['description']);
    $xen_vat->setPercentual($eventData['percentual']);
    
    $gPage_status = $gLocale->getStr('vat_updated.status');
}

$gAction_disp->addEvent('removevat', 'action_removevat');

function action_removevat($eventData)
{
    global $gPage_status, $gLocale;
    
    $xen_vat = new \Innowork\Billing\InnoworkBillingVat($eventData['id']);
    if ($xen_vat->Remove()) {
        $gPage_status = $gLocale->getStr('vat_removed.status');
    }
}

// Banks 

$gAction_disp->addEvent('newbank', 'action_newbank');

function action_newbank($eventData)
{
    global $gPage_status, $gLocale;
    
    $bank = new \Innowork\Billing\InnoworkBillingBank();
    if ($bank->create($eventData['bank'], $eventData['description'])) {
        $gPage_status = $gLocale->getStr('bank_added.status');
    }
}

$gAction_disp->addEvent('editbank', 'action_editbank');

function action_editbank($eventData)
{
    global $gPage_status, $gLocale;
    
    $bank = new \Innowork\Billing\InnoworkBillingBank($eventData['id']);
    
    $bank->setName($eventData['bank']);
    $bank->setDescription($eventData['description']);
    
    $gPage_status = $gLocale->getStr('bank_updated.status');
}

$gAction_disp->addEvent('removebank', 'action_removebank');

function action_removebank($eventData)
{
    global $gPage_status, $gLocale;
    
    $bank = new \Innowork\Billing\InnoworkBillingBank($eventData['id']);
    if ($bank->remove()) {
        $gPage_status = $gLocale->getStr('bank_removed.status');
    }
}

// Payments

$gAction_disp->addEvent('newpayment', 'action_newpayment');

function action_newpayment($eventData)
{
    global $gPage_status, $gLocale;
    
    $xen_payment = new \Innowork\Billing\InnoworkBillingPayment();
    if ($xen_payment->Create($eventData['description'], $eventData['days'], isset($eventData['monthend']) and $eventData['monthend'] == 'on' ? true : false)) {
        $gPage_status = $gLocale->getStr('payment_added.status');
    }
}

$gAction_disp->addEvent('editpayment', 'action_editpayment');

function action_editpayment($eventData)
{
    global $gPage_status, $gLocale;
    
    $xen_payment = new \Innowork\Billing\InnoworkBillingPayment($eventData['id']);
    
    $xen_payment->SetDescription($eventData['description']);
    $xen_payment->SetDays($eventData['days']);
    $xen_payment->SetMonthEnd(isset($eventData['monthend']) and $eventData['monthend'] == 'on' ? true : false);
    
    $gPage_status = $gLocale->getStr('payment_updated.status');
}

$gAction_disp->addEvent('removepayment', 'action_removepayment');

function action_removepayment($eventData)
{
    global $gPage_status, $gLocale;
    
    $xen_payment = new \Innowork\Billing\InnoworkBillingPayment($eventData['id']);
    if ($xen_payment->Remove()) {
        $gPage_status = $gLocale->getStr('payment_removed.status');
    }
}

$gAction_disp->addEvent('setgeneral', 'action_setgeneral');

function action_setgeneral($eventData)
{
    global $gLocale, $gPage_status;
    
    $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
    $sets->SetEmail($eventData['email']);
    $sets->SetSmtpServer($eventData['smtpserver']);
    
    $gPage_status = $gLocale->getStr('settings_set.status');
}

$gAction_disp->addEvent('setdefaults', 'action_setdefaults');

function action_setdefaults($eventData)
{
    global $gLocale, $gPage_status;
    
    $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
    $sets->setDefaultPayment($eventData['paymentid']);
    $sets->setDefaultVat($eventData['vatid']);
    $sets->setDefaultBank($eventData['bankid']);
    
    $gPage_status = $gLocale->getStr('settings_set.status');
}

$gAction_disp->addEvent('settemplates', 'action_settemplates');

function action_settemplates($eventData)
{
    global $gLocale, $gPage_status;
    
    $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
    
    if (is_uploaded_file($eventData['invoice_template']['tmp_name'])) {
        if ($fh = fopen($eventData['invoice_template']['tmp_name'], 'r')) {
            $sets->SetInvoiceTemplate(fread($fh, filesize($eventData['invoice_template']['tmp_name'])));
            
            fclose($fh);
        }
    }
    
    $gPage_status = $gLocale->getStr('settings_set.status');
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher('view');

// Vat

$gMain_disp->addEvent('default', 'main_default');

function main_default($eventData)
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status;
    
    $vats_query = &\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT * ' . 'FROM innowork_billing_vat_codes ' . 'ORDER BY vat');
    
    if ($vats_query->getNumberRows()) {
        $headers[0]['label'] = $gLocale->getStr('vat.header');
        $headers[1]['label'] = $gLocale->getStr('percentual.header');
        $headers[2]['label'] = $gLocale->getStr('description.header');
        
        $gXml_def = '<table>
  <args>
    <headers type="array">' . WuiXml::encode($headers) . '</headers>
  </args>
  <children>';
        
        $row = 0;
        
        while (! $vats_query->eof) {
            $gXml_def .= '<label row="' . $row . '" col="0">
  <args>
    <label type="encoded">' . urlencode($vats_query->getFields('vat')) . '</label>
  </args>
</label>
<label row="' . $row . '" col="1">
  <args>
    <label type="encoded">' . urlencode($vats_query->getFields('percentual')) . '</label>
  </args>
</label>
<label row="' . $row . '" col="2">
  <args>
    <label type="encoded">' . urlencode($vats_query->getFields('description')) . '</label>
  </args>
</label>
<innomatictoolbar row="' . $row . '" col="3">
  <args>
    <frame>false</frame>
    <toolbars type="array">' . WuiXml::encode(array(
                'main' => array(
                    'edit' => array(
                        'label' => $gLocale->getStr('editvat.button'),
                        'themeimage' => 'pencil',
                        'horiz' => 'true',
                        'action' => WuiEventsCall::buildEventsCallString('', array(
                            array(
                                'view',
                                'editvat',
                                array(
                                    'id' => $vats_query->getFields('id')
                                )
                            )
                        ))
                    ),
                    'remove' => array(
                        'label' => $gLocale->getStr('removevat.button'),
                        'themeimage' => 'mathsub',
                        'horiz' => 'true',
                        'needconfirm' => 'true',
                        'confirmmessage' => $gLocale->getStr('removevat.confirm'),
                        'action' => WuiEventsCall::buildEventsCallString('', array(
                            array(
                                'view',
                                'default',
                                ''
                            ),
                            array(
                                'action',
                                'removevat',
                                array(
                                    'id' => $vats_query->getFields('id')
                                )
                            )
                        ))
                    )
                )
            )) . '</toolbars>
  </args>
</innomatictoolbar>';
            $vats_query->MoveNext();
            $row ++;
        }
        
        $gXml_def .= '  </children>
</table>';
    } else {
        $gPage_status = $gLocale->getStr('novats.status');
    }
}

$gMain_disp->addEvent('newvat', 'main_newvat');

function main_newvat($eventData)
{
    global $gLocale, $gXml_def;
    
    $gXml_def = '<vertgroup>
  <children>

    <form><name>newvat</name>
      <args>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            ''
        ),
        array(
            'action',
            'newvat',
            ''
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('vat.label')) . '</label>
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
                <label type="encoded">' . urlencode($gLocale->getStr('percentual.label')) . '</label>
              </args>
            </label>

            <string row="1" col="1"><name>percentual</name>
              <args>
                <disp>action</disp>
                <size>5</size>
              </args>
            </string>

            <label row="2" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('description.label')) . '</label>
              </args>
            </label>

            <text row="2" col="1"><name>description</name>
              <args>
                <disp>action</disp>
                <rows>3</rows>
                <cols>50</cols>
              </args>
            </text>
                    
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
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            ''
        ),
        array(
            'action',
            'newvat',
            ''
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('newvat.submit')) . '</label>
            <formsubmit>newvat</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent('editvat', 'main_editvat');

function main_editvat($eventData)
{
    global $gLocale, $gXml_def;
    
    $vat_query = &\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT * ' . 'FROM innowork_billing_vat_codes ' . 'WHERE id=' . $eventData['id']);
    
    $gXml_def = '<vertgroup>
  <children>

    <form><name>editvat</name>
      <args>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            ''
        ),
        array(
            'action',
            'editvat',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('vat.label')) . '</label>
              </args>
            </label>

            <string row="0" col="1"><name>vat</name>
              <args>
                <disp>action</disp>
                <size>15</size>
                <value type="encoded">' . urlencode($vat_query->getFields('vat')) . '</value>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('percentual.label')) . '</label>
              </args>
            </label>

            <string row="1" col="1"><name>percentual</name>
              <args>
                <disp>action</disp>
                <size>5</size>
                <value type="encoded">' . urlencode($vat_query->getFields('percentual')) . '</value>
              </args>
            </string>

            <label row="2" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('description.label')) . '</label>
              </args>
            </label>

            <text row="2" col="1"><name>description</name>
              <args>
                <disp>action</disp>
                <rows>3</rows>
                <cols>50</cols>
                <value type="encoded">' . urlencode($vat_query->getFields('description')) . '</value>
              </args>
            </text>
                    
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
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'default',
            ''
        ),
        array(
            'action',
            'editvat',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('editvat.submit')) . '</label>
            <formsubmit>editvat</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

// Banks

$gMain_disp->addEvent('banks', 'main_banks');

function main_banks($eventData)
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status;
    
    $banks_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT * ' . 'FROM innowork_billing_banks ' . 'ORDER BY bank');
    
    if ($banks_query->getNumberRows()) {
        $headers[0]['label'] = $gLocale->getStr('bank.header');
        $headers[1]['label'] = $gLocale->getStr('description.header');
        
        $gXml_def = '<table>
  <args>
    <headers type="array">' . WuiXml::encode($headers) . '</headers>
  </args>
  <children>';
        
        $row = 0;
        
        while (! $banks_query->eof) {
            $gXml_def .= '<label row="' . $row . '" col="0">
  <args>
    <label type="encoded">' . urlencode($banks_query->getFields('bank')) . '</label>
  </args>
</label>
<label row="' . $row . '" col="1">
  <args>
    <label type="encoded">' . urlencode($banks_query->getFields('description')) . '</label>
  </args>
</label>
<innomatictoolbar row="' . $row . '" col="2">
  <args>
    <frame>false</frame>
    <toolbars type="array">' . WuiXml::encode(array(
                'main' => array(
                    'edit' => array(
                        'label' => $gLocale->getStr('editbank.button'),
                        'themeimage' => 'pencil',
                        'horiz' => 'true',
                        'action' => WuiEventsCall::buildEventsCallString('', array(
                            array(
                                'view',
                                'editbank',
                                array(
                                    'id' => $banks_query->getFields('id')
                                )
                            )
                        ))
                    ),
                    'remove' => array(
                        'label' => $gLocale->getStr('removebank.button'),
                        'themeimage' => 'mathsub',
                        'horiz' => 'true',
                        'needconfirm' => 'true',
                        'confirmmessage' => $gLocale->getStr('removebank.confirm'),
                        'action' => WuiEventsCall::buildEventsCallString('', array(
                            array(
                                'view',
                                'banks',
                                ''
                            ),
                            array(
                                'action',
                                'removebank',
                                array(
                                    'id' => $banks_query->getFields('id')
                                )
                            )
                        ))
                    )
                )
            )) . '</toolbars>
  </args>
</innomatictoolbar>';
            $banks_query->MoveNext();
            $row ++;
        }
        
        $gXml_def .= '  </children>
</table>';
    } else {
        $gPage_status = $gLocale->getStr('nobanks.status');
    }
}

$gMain_disp->addEvent('newbank', 'main_newbank');

function main_newbank($eventData)
{
    global $gLocale, $gXml_def;
    
    $gXml_def = '<vertgroup>
  <children>

    <form><name>newbank</name>
      <args>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'banks',
            ''
        ),
        array(
            'action',
            'newbank',
            ''
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('bank.label')) . '</label>
              </args>
            </label>

            <string row="0" col="1"><name>bank</name>
              <args>
                <disp>action</disp>
                <size>15</size>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('description.label')) . '</label>
              </args>
            </label>

            <text row="1" col="1"><name>description</name>
              <args>
                <disp>action</disp>
                <rows>3</rows>
                <cols>50</cols>
              </args>
            </text>
                    
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
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'banks',
            ''
        ),
        array(
            'action',
            'newbank',
            ''
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('newbank.submit')) . '</label>
            <formsubmit>newbank</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent('editbank', 'main_editbank');

function main_editbank($eventData)
{
    global $gLocale, $gXml_def;
    
    $bank_query = &\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT * ' . 'FROM innowork_billing_banks ' . 'WHERE id=' . $eventData['id']);
    
    $gXml_def = '<vertgroup>
  <children>

    <form><name>editbank</name>
      <args>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'banks',
            ''
        ),
        array(
            'action',
            'editbank',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('bank.label')) . '</label>
              </args>
            </label>

            <string row="0" col="1"><name>bank</name>
              <args>
                <disp>action</disp>
                <size>15</size>
                <value type="encoded">' . urlencode($bank_query->getFields('bank')) . '</value>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('description.label')) . '</label>
              </args>
            </label>

            <text row="1" col="1"><name>description</name>
              <args>
                <disp>action</disp>
                <rows>3</rows>
                <cols>50</cols>
                <value type="encoded">' . urlencode($bank_query->getFields('description')) . '</value>
              </args>
            </text>
                    
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
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'banks',
            ''
        ),
        array(
            'action',
            'editbank',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('editbank.submit')) . '</label>
            <formsubmit>editbank</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

// Payments

$gMain_disp->addEvent('payments', 'main_payments');

function main_payments($eventData)
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status;
    
    $payments_query = &\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT * ' . 'FROM innowork_billing_payments ' . 'ORDER BY description');
    
    if ($payments_query->getNumberRows()) {
        $headers[0]['label'] = $gLocale->getStr('payment.header');
        $headers[1]['label'] = $gLocale->getStr('days.header');
        $headers[2]['label'] = $gLocale->getStr('monthend.header');
        
        $gXml_def = '<table>
  <args>
    <headers type="array">' . WuiXml::encode($headers) . '</headers>
  </args>
  <children>';
        
        $row = 0;
        
        while (! $payments_query->eof) {
            $gXml_def .= '<label row="' . $row . '" col="0">
  <args>
    <label type="encoded">' . urlencode($payments_query->getFields('description')) . '</label>
  </args>
</label>
<label row="' . $row . '" col="1">
  <args>
    <label type="encoded">' . urlencode($payments_query->getFields('days')) . '</label>
  </args>
</label>
<label row="' . $row . '" col="2">
  <args>
    <label type="encoded">' . urlencode($payments_query->getFields('monthend') == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue ? $gLocale->getStr('yes.label') : $gLocale->getStr('no.label')) . '</label>
  </args>
</label>
<innomatictoolbar row="' . $row . '" col="3">
  <args>
    <frame>false</frame>
    <toolbars type="array">' . WuiXml::encode(array(
                'main' => array(
                    'edit' => array(
                        'label' => $gLocale->getStr('editpayment.button'),
                        'themeimage' => 'pencil',
                        'horiz' => 'true',
                        'action' => WuiEventsCall::buildEventsCallString('', array(
                            array(
                                'view',
                                'editpayment',
                                array(
                                    'id' => $payments_query->getFields('id')
                                )
                            )
                        ))
                    ),
                    'remove' => array(
                        'label' => $gLocale->getStr('removepayment.button'),
                        'themeimage' => 'mathsub',
                        'horiz' => 'true',
                        'needconfirm' => 'true',
                        'confirmmessage' => $gLocale->getStr('removepayment.confirm'),
                        'action' => WuiEventsCall::buildEventsCallString('', array(
                            array(
                                'view',
                                'payments',
                                ''
                            ),
                            array(
                                'action',
                                'removepayment',
                                array(
                                    'id' => $payments_query->getFields('id')
                                )
                            )
                        ))
                    )
                )
            )) . '</toolbars>
  </args>
</innomatictoolbar>';
            $payments_query->MoveNext();
            $row ++;
        }
        
        $gXml_def .= '  </children>
</table>';
    } else {
        $gPage_status = $gLocale->getStr('nopayments.status');
    }
}

$gMain_disp->addEvent('newpayment', 'main_newpayment');

function main_newpayment($eventData)
{
    global $gLocale, $gXml_def;
    
    $gXml_def = '<vertgroup>
  <children>

    <form><name>newpayment</name>
      <args>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'payments',
            ''
        ),
        array(
            'action',
            'newpayment',
            ''
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('payment.label')) . '</label>
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
                <label type="encoded">' . urlencode($gLocale->getStr('days.label')) . '</label>
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
                <label type="encoded">' . urlencode($gLocale->getStr('monthend.label')) . '</label>
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
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'payments',
            ''
        ),
        array(
            'action',
            'newpayment',
            ''
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('newpayment.submit')) . '</label>
            <formsubmit>newpayment</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent('editpayment', 'main_editpayment');

function main_editpayment($eventData)
{
    global $gLocale, $gXml_def;
    
    $payment_query = &\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT * ' . 'FROM innowork_billing_payments ' . 'WHERE id=' . $eventData['id']);
    
    $gXml_def = '<vertgroup>
  <children>

    <form><name>editpayment</name>
      <args>
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'payments',
            ''
        ),
        array(
            'action',
            'editpayment',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="0" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('payment.label')) . '</label>
              </args>
            </label>

            <string row="0" col="1"><name>description</name>
              <args>
                <disp>action</disp>
                <size>25</size>
                <value type="encoded">' . urlencode($payment_query->getFields('description')) . '</value>
              </args>
            </string>

            <label row="1" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('days.label')) . '</label>
              </args>
            </label>

            <string row="1" col="1"><name>days</name>
              <args>
                <disp>action</disp>
                <size>5</size>
                <value type="encoded">' . urlencode($payment_query->getFields('days')) . '</value>
              </args>
            </string>

            <label row="2" col="0">
              <args>
                <label type="encoded">' . urlencode($gLocale->getStr('monthend.label')) . '</label>
              </args>
            </label>

            <checkbox row="2" col="1"><name>monthend</name>
              <args>
                <disp>action</disp>
                <checked>' . ($payment_query->getFields('monthend') == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue ? 'true' : 'false') . '</checked>
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
            <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'payments',
            ''
        ),
        array(
            'action',
            'editpayment',
            array(
                'id' => $eventData['id']
            )
        )
    ))) . '</action>
            <label type="encoded">' . urlencode($gLocale->getStr('editpayment.submit')) . '</label>
            <formsubmit>editpayment</formsubmit>
          </args>
        </button>

  </children>
</vertgroup>';
}

function settings_tab_action_builder($tab)
{
    return WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'settings',
            array(
                'tabpage' => $tab
            )
        )
    ));
}

$gMain_disp->addEvent('settings', 'main_settings');

function main_settings($eventData)
{
    global $gLocale, $gPage_title, $gXml_def;
    
    $tabs[0]['label'] = $gLocale->getStr('general_settings.tab');
    $tabs[1]['label'] = $gLocale->getStr('defaults_settings.tab');
    $tabs[2]['label'] = $gLocale->getStr('templates_settings.tab');
    
    $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
    
    $vats_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT id,vat ' . 'FROM innowork_billing_vat_codes ' . 'ORDER BY vat');
    
    $vats[0] = $gLocale->getStr('novat.label');
    while (! $vats_query->eof) {
        $vats[$vats_query->getFields('id')] = $vats_query->getFields('vat');
        $vats_query->MoveNext();
    }
    
    $payments_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('SELECT id,description ' . 'FROM innowork_billing_payments ' . 'ORDER BY description');
    
    $payments[0] = $gLocale->getStr('nopayment.label');
    while (! $payments_query->eof) {
        $payments[$payments_query->getFields('id')] = $payments_query->getFields('description');
        $payments_query->MoveNext();
    }
    
    $banks_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->execute('SELECT id,bank ' . 'FROM innowork_billing_banks ' . 'ORDER BY bank');
    
    $banks[0] = $gLocale->getStr('nobank.label');
    while (!$banks_query->eof) {
        $banks[$banks_query->getFields('id')] = $banks_query->getFields('bank');
        $banks_query->moveNext();
    }

    $gXml_def = '<vertgroup>
  <children>

    <label>
      <args>
        <label type="encoded">' . urlencode($gLocale->getStr('settings.label')) . '</label>
        <bold>true</bold>
      </args>
    </label>

    <tab><name>settings</name>
      <args>
        <tabs type="array">' . WuiXml::encode($tabs) . '</tabs>
        <activetab>' . (isset($eventData['tabpage']) ? $eventData['tabpage'] : '') . '</activetab>
        <tabactionfunction>settings_tab_action_builder</tabactionfunction>
      </args>
      <children>

        <vertgroup>
          <children>

            <form><name>settings</name>
              <args>
                <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'settings',
            ''
        ),
        array(
            'action',
            'setgeneral',
            ''
        )
    ))) . '</action>
              </args>
              <children>

                <grid>
                  <children>

                    <label row="0" col="0">
                      <args>
                        <label type="encoded">' . urlencode($gLocale->getStr('email.label')) . '</label>
                      </args>
                    </label>

                    <string row="0" col="1"><name>email</name>
                      <args>
                        <disp>action</disp>
                        <size>25</size>
                        <value type="encoded">' . urlencode($sets->GetEmail()) . '</value>
                      </args>
                    </string>

                    <label row="1" col="0">
                      <args>
                        <label type="encoded">' . urlencode($gLocale->getStr('smtpserver.label')) . '</label>
                      </args>
                    </label>

                    <string row="1" col="1"><name>smtpserver</name>
                      <args>
                        <disp>action</disp>
                        <size>25</size>
                        <value type="encoded">' . urlencode($sets->GetSmtpServer()) . '</value>
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
                <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'settings',
            ''
        ),
        array(
            'action',
            'setgeneral',
            ''
        )
    ))) . '</action>
                <label type="encoded">' . urlencode($gLocale->getStr('apply.submit')) . '</label>
                <formsubmit>settings</formsubmit>
              </args>
            </button>

          </children>
        </vertgroup>

        <vertgroup>
          <children>

            <form><name>settings</name>
              <args>
                <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'settings',
            ''
        ),
        array(
            'action',
            'setdefaults',
            ''
        )
    ))) . '</action>
              </args>
              <children>

                <grid>
                  <children>

                    <label row="0" col="0">
                      <args>
                        <label type="encoded">' . urlencode($gLocale->getStr('default_vat.label')) . '</label>
                      </args>
                    </label>

                    <combobox row="0" col="1"><name>vatid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">' . WuiXml::encode($vats) . '</elements>
                        <default>' . $sets->GetDefaultVat() . '</default>
                      </args>
                    </combobox>

                    <label row="1" col="0">
                      <args>
                        <label type="encoded">' . urlencode($gLocale->getStr('default_payment.label')) . '</label>
                      </args>
                    </label>

                    <combobox row="1" col="1"><name>paymentid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">' . WuiXml::encode($payments) . '</elements>
                        <default>' . $sets->GetDefaultPayment() . '</default>
                      </args>
                    </combobox>

                    <label row="2" col="0">
                      <args>
                        <label type="encoded">' . urlencode($gLocale->getStr('default_bank.label')) . '</label>
                      </args>
                    </label>

                    <combobox row="2" col="1"><name>bankid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">' . WuiXml::encode($banks) . '</elements>
                        <default>' . $sets->getDefaultBank() . '</default>
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
                <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'settings',
            ''
        ),
        array(
            'action',
            'setdefaults',
            ''
        )
    ))) . '</action>
                <label type="encoded">' . urlencode($gLocale->getStr('apply.submit')) . '</label>
                <formsubmit>settings</formsubmit>
              </args>
            </button>

          </children>
        </vertgroup>

        <vertgroup>
          <children>

            <form><name>settings</name>
              <args>
                <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'settings',
            ''
        ),
        array(
            'action',
            'settemplates',
            ''
        )
    ))) . '</action>
              </args>
              <children>

                <grid>
                  <children>

                    <label row="0" col="0">
                      <args>
                        <label type="encoded">' . urlencode($gLocale->getStr('invoice_template.label')) . '</label>
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
                <action type="encoded">' . urlencode(WuiEventsCall::buildEventsCallString('', array(
        array(
            'view',
            'settings',
            ''
        ),
        array(
            'action',
            'settemplates',
            ''
        )
    ))) . '</action>
                <label type="encoded">' . urlencode($gLocale->getStr('apply.submit')) . '</label>
                <formsubmit>settings</formsubmit>
              </args>
            </button>

          </children>
        </vertgroup>

      </children>
    </tab>

  </children>
</vertgroup>';
    
    $gPage_title = $gLocale->getStr('settings.title');
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
        new WuiInnomaticToolBar('core', array(
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

?>
