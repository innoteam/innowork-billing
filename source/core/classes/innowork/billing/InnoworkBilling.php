<?php
/*
 *   Copyright (C) 2003-2004 Solarix
 *
 */


OpenLibrary( 'dblayer.library' );
OpenLibrary( 'logger.library' );
OpenLibrary( 'xencore.library' );

define( 'XENBILLING_INVOICE_ITEM_TYPE', 'billing' );

class XenInvoice extends XenItem
{
    var $mTable = 'innowork_billing_invoices';
    var $mNewDispatcher = 'main';
    var $mNewEvent = 'newinvoice';
    var $mNoAcl = true;
    //var $mNoLog = true;
    var $mNoTrash = false;
    var $mConvertible = true;

    function XenInvoice(
        &$rampDb,
        &$rsiteDb,
        $invoiceId = 0
        )
    {
        $this->XenItem(
            $rampDb,
            $rsiteDb,
            XENBILLING_INVOICE_ITEM_TYPE,
            $invoiceId
            );

        $this->mKeys['number'] = 'text';
        $this->mKeys['customerid'] = 'table:xendirectorycompanies:companyname:integer';
        $this->mKeys['projectid'] = 'table:xenprojects:name:integer';
        $this->mKeys['emissiondate'] = 'timestamp';
        $this->mKeys['duedate'] = 'timestamp';
        $this->mKeys['amount'] = 'text';
        $this->mKeys['total'] = 'text';
        $this->mKeys['paidamount'] = 'text';
        $this->mKeys['accountmanager'] = 'text';

        $this->mSearchResultKeys[] = 'number';
        $this->mSearchResultKeys[] = 'emissiondate';
        $this->mSearchResultKeys[] = 'customerid';
        $this->mSearchResultKeys[] = 'projectid';
        $this->mSearchResultKeys[] = 'duedate';
        $this->mSearchResultKeys[] = 'amount';
        $this->mSearchResultKeys[] = 'total';
        $this->mSearchResultKeys[] = 'paidamount';
        $this->mSearchResultKeys[] = 'accountmanager';

        $this->mViewableSearchResultKeys[] = 'number';
        $this->mViewableSearchResultKeys[] = 'emissiondate';
        $this->mViewableSearchResultKeys[] = 'customerid';
        $this->mViewableSearchResultKeys[] = 'projectid';
        $this->mViewableSearchResultKeys[] = 'duedate';
        $this->mViewableSearchResultKeys[] = 'total';
        $this->mViewableSearchResultKeys[] = 'paidamount';

        $this->mSearchOrderBy = 'emissiondate DESC,number DESC';
        $this->mShowDispatcher = 'main';
        $this->mShowEvent = 'showinvoice';

        $this->mGenericFields['companyid'] = 'customerid';
        $this->mGenericFields['projectid'] = 'projectid';
        $this->mGenericFields['title'] = '';
        $this->mGenericFields['content'] = '';
        $this->mGenericFields['binarycontent'] = '';
    }

    function _Create(
        $params,
        $userId
        )
    {
        $result = false;

        if (
            !isset( $params['projectid'] )
            or !strlen( $params['projectid'] )
            ) $params['projectid'] = '0';

        if (
            !isset( $params['customerid'] )
            or !strlen( $params['customerid'] )
            ) $params['customerid'] = '0';

        if ( count( $params ) )
        {
            global $gEnv;

            $item_id = $this->mrSiteDb->NextSeqValue( $this->mTable.'_id_seq' );

            $key_pre = $value_pre = $keys = $values = '';

            OpenLibrary( 'locale.library' );
            $country = new LocaleCountry( $GLOBALS['gEnv']['user']['locale']['country'] );

            while ( list( $key, $val ) = each( $params ) )
            {
                $key_pre = ',';
                $value_pre = ',';

                switch ( $key )
                {
                case 'number':
                case 'amount':
                case 'vat':
                case 'total':
                case 'paidamount':
                case 'accountmanager':
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrSiteDb->Format_Text( $val );
                    break;

                case 'emissiondate':
                case 'duedate':
                    $date_array = $country->GetDateArrayFromShortDateStamp( $val );
                    $val = $this->mrSiteDb->GetTimestampFromDateArray( $date_array );
                    unset( $date_array );

                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrSiteDb->Format_Text( $val );
                    break;

                case 'customerid':
                case 'projectid':
                case 'paymentid':
                    if ( !strlen( $key ) ) $key = 0;
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$val;
                    break;

                default:
                    break;
                }
            }

            if ( strlen( $values ) )
            {
                if ( $this->mrSiteDb->Execute( 'INSERT INTO '.$this->mTable.' '.
                                               '(id,ownerid'.$keys.') '.
                                               'VALUES ('.$item_id.','.
                                               $userId.
                                               $values.')' ) )
                {
                    $this->SetLastInvoiceNumber( $params['number'] );

                    $result = $item_id;
                }
            }
        }

        $this->_mCreationAcl = XENCORE_ACL_TYPE_PUBLIC;

        return $result;
    }

    function _Edit(
        $params
        )
    {
        $result = false;

        if ( $this->mItemId )
        {
            if ( count( $params ) )
            {
                $start = 1;
                $update_str = '';

                global $gEnv;
                $country = new LocaleCountry( $gEnv['user']['locale']['country'] );

                while ( list( $field, $value ) = each( $params ) )
                {
                    if ( $field != 'id' )
                    {
                        switch ( $field )
                        {
                        case 'number':
                        case 'amount':
                        case 'vat':
                        case 'total':
                        case 'paidamount':
                        case 'accountmanager':
                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$this->mrSiteDb->Format_Text( $value );
                            $start = 0;
                            break;

                        case 'emissiondate':
                        case 'duedate':
                            $date_array = $country->GetDateArrayFromShortDateStamp( $value );
                            $value = $this->mrSiteDb->GetTimestampFromDateArray( $date_array );
                            unset( $date_array );

                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$this->mrSiteDb->Format_Text( $value );
                            $start = 0;
                            break;


                        case 'customerid':
                        case 'projectid':
                        case 'paymentid':
                           if ( !strlen( $value ) ) $value = 0;
                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$value;
                            $start = 0;
                            break;

                        default:
                            break;
                        }
                    }
                }

                $query = &$this->mrSiteDb->Execute(
                    'UPDATE '.$this->mTable.' '.
                    'SET '.$update_str.' '.
                    'WHERE id='.$this->mItemId );

                if ( $query ) $result = TRUE;
            }
        }

        return $result;
    }

    function _Remove(
        $userId
        )
    {
        $result = FALSE;

        $result = $this->mrSiteDb->Execute(
            'DELETE FROM '.$this->mTable.' '.
            'WHERE id='.$this->mItemId
            );

        if ( $result )
        {
            $GLOBALS['gEnv']['site']['db']->Execute(
                'DELETE FROM innowork_billing_invoices_rows '.
                'WHERE invoiceid='.$this->mItemId
                );
        }

        return $result;
    }

    function _GetItem(
        $userId
        )
    {
        $result = FALSE;

        $item_query = &$this->mrSiteDb->Execute(
            'SELECT * '.
            'FROM '.$this->mTable.' '.
            'WHERE id='.$this->mItemId
            );

        if (
            is_object( $item_query )
            and $item_query->NumRows()
            )
        {
            $result = $item_query->Fields();
        }

        return $result;
    }

    function AddRow(
        $description,
        $amount,
        $vatId,
        $quantity,
        $discount
        )
    {
        $result = false;

        $vatId = (int)$vatId;
        if ( !strlen( $vatId ) ) $vatId = 0;

        if ( $this->mItemId )
        {
            OpenLibrary( 'locale.library' );
            $locale_country = new LocaleCountry( $GLOBALS['gEnv']['site']['locale']['country'] );

            $amount = str_replace( ',', '.', $amount );
            $amount = number_format(
                $amount,
                $locale_country->FractDigits(),
                '.',
                ''
                );

            $id = $GLOBALS['gEnv']['site']['db']->NextSeqValue( 'innowork_billing_invoices_rows_id_seq' );

            if ( $GLOBALS['gEnv']['site']['db']->Execute(
                'INSERT INTO innowork_billing_invoices_rows (id, invoiceid, description, amount, quantity, discount, vatid ) '.
                'VALUES ('.$id.','.
                $this->mItemId.','.
                $GLOBALS['gEnv']['site']['db']->Format_Text( $description ).','.
                $GLOBALS['gEnv']['site']['db']->Format_Text( $amount ).','.
                $GLOBALS['gEnv']['site']['db']->Format_Text( (int)$quantity ).','.
                $GLOBALS['gEnv']['site']['db']->Format_Text( (int)$discount ).','.
                $vatId.')' ) )
            {
                $this->SetInvoiceTotals(
                    $this->CalculateInvoiceTotals()
                    );

                $result = true;
            }
        }

        return $result;
    }

    function GetRow(
        $rowId
        )
    {
        $result = array();

        $rowId = (int)$rowId;
        if ( !strlen( $rowId ) ) $rowId = 0;

        if (
            $this->mItemId
            and
            $rowId
            )
        {
            $query = &$GLOBALS['gEnv']['site']['db']->Execute(
                'SELECT * '.
                'FROM innowork_billing_invoices_rows '.
                'WHERE invoiceid='.$this->mItemId.' '.
                'AND id='.$rowId
                );

            if ( $query->NumRows() )
            {
                $result['id'] = $rowId;
                $result['invoiceid'] = $query->Fields( 'invoiceid' );
                $result['description'] = $query->Fields( 'description' );
                $result['vatid'] = $query->Fields( 'vatid' );
                $result['amount'] = $query->Fields( 'amount' );
                $result['quantity'] = $query->Fields( 'quantity' );
                $result['discount'] = $query->Fields( 'discount' );

            }
        }

        return $result;
    }

    function EditRow(
        $rowId,
        $description,
        $amount,
        $vatId,
        $quantity,
        $discount
        )
    {
        $result = false;

        $vatId = (int)$vatId;
        if ( !strlen( $vatId ) ) $vatId = 0;
        $rowId = (int)$rowId;
        if ( !strlen( $rowId ) ) $rowId = 0;

        if (
            $this->mItemId
            and
            $rowId
            )
        {
            OpenLibrary( 'locale.library' );
            $locale_country = new LocaleCountry( $GLOBALS['gEnv']['site']['locale']['country'] );

            $amount = str_replace( ',', '.', $amount );
            $amount = number_format(
                $amount,
                $locale_country->FractDigits(),
                '.',
                ''
                );

            $old_row = $this->GetRow( $rowId );

            if ( $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_invoices_rows SET '.
                'description='.$GLOBALS['gEnv']['site']['db']->Format_Text( $description ).','.
                'amount='.$GLOBALS['gEnv']['site']['db']->Format_Text( $amount ).','.
                'quantity='.$GLOBALS['gEnv']['site']['db']->Format_Text( (int)$quantity ).','.
                'discount='.$GLOBALS['gEnv']['site']['db']->Format_Text( (int)$discount ).','.
                'vatid='.$vatId.' '.
                'WHERE id='.$rowId.' '.
                'AND invoiceid='.$this->mItemId ) )
            {
                $this->SetInvoiceTotals(
                    $this->CalculateInvoiceTotals()
                    );

                $result = true;
            }
        }

        return $result;
    }

    function RemoveRow(
        $rowId
        )
    {
        $result = false;

        $rowId = (int)$rowId;
        if ( !strlen( $rowId ) ) $rowId = 0;

        if (
            $this->mItemId
            and
            $rowId
            )
        {
            if ( $GLOBALS['gEnv']['site']['db']->Execute(
                'DELETE FROM innowork_billing_invoices_rows '.
                'WHERE invoiceid='.$this->mItemId.' '.
                'AND id='.$rowId
                ) )
            {
                $this->SetInvoiceTotals(
                    $this->CalculateInvoiceTotals()
                    );

                $result = true;
            }
        }

        return $result;
    }

    function GetRows()
    {
        $result = array();

        if ( $this->mItemId )
        {
            $locale_country = new LocaleCountry(
                $GLOBALS['gEnv']['site']['locale']['country']
                );

            $rows_query = &$GLOBALS['gEnv']['site']['db']->Execute(
                'SELECT * '.
                'FROM innowork_billing_invoices_rows '.
                'WHERE invoiceid='.$this->mItemId.' '.
                'ORDER BY id'
                );

            $vats_query = &$GLOBALS['gEnv']['site']['db']->Execute(
                'SELECT id,percentual '.
                'FROM innowork_billing_vat_codes '
                );

            $vats = array();
            while ( !$vats_query->eof )
            {
                $vats[$vats_query->Fields( 'id' )] = $vats_query->Fields( 'percentual' );
                $vats_query->MoveNext();
            }

            while ( !$rows_query->eof )
            {
                $vat = 0;
                $quantity = $rows_query->Fields( 'quantity' );
                if ( !(int)$quantity ) $quantity = 1;
                
				$result['amount'] += $tmp_row_amount = ( ( $rows_query->Fields( 'amount' ) - ( $rows_query->Fields( 'amount' ) * $rows_query->Fields( 'discount' ) / 100 ) ) * $quantity );

                if (
                    $rows_query->Fields( 'vatid' ) != 0
                    and
                    isset( $vats[$rows_query->Fields( 'vatid' )] )
                    )
                {
                    $vat = round(
                        $tmp_row_amount  * $vats[$rows_query->Fields( 'vatid' )] / 100,
                        $locale_country->FractDigits()
                    );
                }

                $result[] = array(
                    'id' => $rows_query->Fields( 'id' ),
                    'description' => $rows_query->Fields( 'description' ),
                    'amount' => number_format(
                        $rows_query->Fields( 'amount' ),
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                        ),
					'totalamount' => number_format(
                        $tmp_row_amount,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                        ),
					'quantity' => $quantity,
					'discount' => $rows_query->Fields( 'discount' ),
                    'vatid' => $rows_query->Fields( 'vatid' ),
                    'vat' => number_format(
                        $vat,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                        ),
                    'total' => number_format(
                        $tmp_row_amount + $vat,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        $locale_country->MoneyThousandsSeparator()
                        ),
                    'unf_amount' => number_format(
                        $rows_query->Fields( 'amount' ),
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        ''
                        ),
                    'unf_totalamount' => number_format(
                        $tmp_row_amount,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        ''
                        ),
                    'unf_vat' => number_format(
                        $vat,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        ''
                        ),
                    'unf_total' => number_format(
                        $tmp_row_amount + $vat,
                        $locale_country->FractDigits(),
                        $locale_country->MoneyDecimalSeparator(),
                        ''
                        )
                    );

                $rows_query->MoveNext();
            }
        }

        return $result;
    }

    function CalculateInvoiceTotals()
    {
        $result = array();

        if ( $this->mItemId )
        {
            OpenLibrary( 'locale.library' );
            $locale_country = new LocaleCountry( $GLOBALS['gEnv']['site']['locale']['country'] );

            $result['amount'] = $result['vat'] = $result['total'] = 0;

            $vats = array();

            $vats_query = &$GLOBALS['gEnv']['site']['db']->Execute(
                'SELECT id,percentual '.
                'FROM innowork_billing_vat_codes'
                );

            while ( !$vats_query->eof )
            {
                $vats[$vats_query->Fields( 'id' )] = $vats_query->Fields( 'percentual' );

                $vats_query->MoveNext();
            }

            $rows_query = &$GLOBALS['gEnv']['site']['db']->Execute(
                'SELECT amount,quantity,discount,vatid '.
                'FROM innowork_billing_invoices_rows '.
                'WHERE invoiceid='.$this->mItemId
                );

            while ( !$rows_query->eof )
            {
            	$result['amount'] += $tmp_row_amount = ( ( $rows_query->Fields( 'amount' ) - ( $rows_query->Fields( 'amount' )*  $rows_query->Fields( 'discount' ) / 100 ) ) * $rows_query->Fields( 'quantity' ) );

                if (
                    $rows_query->Fields( 'vatid' )
                    and
                    isset( $vats[$rows_query->Fields( 'vatid' )] )
                    )
                {
                    $result['vat'] += round(
                        $tmp_row_amount * $vats[$rows_query->Fields( 'vatid' )] / 100,
                        $locale_country->FractDigits()
                        );
                }

                $rows_query->MoveNext();
            }

            $result['amount'] = number_format(
                $result['amount'],
                $locale_country->FractDigits(),
                '.',
                ''
                );

            $result['vat'] = number_format(
                $result['vat'],
                $locale_country->FractDigits(),
                '.',
                ''
                );

            $result['total'] = number_format(
                $result['amount'] + $result['vat'],
                $locale_country->FractDigits(),
                '.',
                ''
                );
        }

        return $result;
    }

    function GetInvoiceTotals()
    {
        $result = array();

        if ( $this->mItemId )
        {
            $query = &$GLOBALS['gEnv']['site']['db']->Execute(
                'SELECT amount,vat,total '.
                'FROM innowork_billing_invoices '.
                'WHERE id='.$this->mItemId
                );

            if ( $query->NumRows() )
            {
                $result['amount'] = $query->Fields( 'amount' );
                $result['vat'] = $query->Fields( 'vat' );
                $result['total'] = $query->Fields( 'total' );
            }
        }

        return $result;
    }

    function SetInvoiceTotals(
        $totals
        )
    {
        $result = false;

        if ( $this->mItemId )
        {
            if ( $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_invoices '.
                'SET amount='.$GLOBALS['gEnv']['site']['db']->Format_Text( $totals['amount'] ).','.
                'vat='.$GLOBALS['gEnv']['site']['db']->Format_Text( $totals['vat'] ).','.
                'total='.$GLOBALS['gEnv']['site']['db']->Format_Text( $totals['total'] ).' '.
                'WHERE id='.$this->mItemId
                ) )
            {
                $this->cleanCache();
                $result = true;
            }
        }

        return $result;
    }

    function SetPaidAmount(
        $amount
        )
    {
        $result = false;

        if ( $this->mItemId )
        {
            OpenLibrary( 'locale.library' );
            $locale_country = new LocaleCountry( $GLOBALS['gEnv']['site']['locale']['country'] );

            $amount = str_replace( ',', '.', $amount );

            $amount = number_format(
                $amount,
                $locale_country->FractDigits(),
                '.',
                ''
                );

            if ( $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_invoices '.
                'SET paidamount='.$GLOBALS['gEnv']['site']['db']->Format_Text( $amount ).' '.
                'WHERE id='.$this->mItemId
                ) )
            {
                $this->cleanCache();
                $result = true;
            }
        }

        return $result;
    }

    function SetLastInvoiceNumber(
        $number
        )
    {
        OpenLibrary( 'sites.library' );

        $site_sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        $site_sets->SetKey(
            'xen-billing-lastinvoicenumber',
            $number
            );

        return true;
    }

    function GetLastInvoiceNumber()
    {
        OpenLibrary( 'sites.library' );

        $site_sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        $result = $site_sets->GetKey( 'xen-billing-lastinvoicenumber' );

        if ( !strlen( $result ) ) $result = 0;

        return $result;
    }

    function CreateHtmlInvoice()
    {
        OpenLibrary( 'rhtemplate.library' );
        OpenLibrary( 'locale.library' );
        OpenLibrary( 'xenprojects.library' );
        OpenLibrary( 'xendirectory.library' );

        $locale_country = new LocaleCountry(
            $GLOBALS['gEnv']['site']['locale']['country']
            );

        $sets = new XenBilling_SettingsHandler();

        $template = new Rh_Template();
        $template->files['invoice'] = $sets->GetInvoiceTemplate();

        unset( $sets );

        // Invoice data

        $inv_data = $this->GetItem();
        $inv_rows = $this->GetRows();
        //print_r($inv_data);

        $payment = new XenBilling_Payment( $inv_data['paymentid'] );

        $template->Register( 'invoice', 'tpl_invoice_number', $inv_data['number'] );
        $template->Register( 'invoice', 'tpl_invoice_emissiondate', $locale_country->FormatShortArrayDate(
                $GLOBALS['gEnv']['site']['db']->GetDateArrayFromTimestamp(
                    $inv_data['emissiondate']
                    ) ) );
        $template->Register( 'invoice', 'tpl_invoice_duedate', $locale_country->FormatShortArrayDate(
                $GLOBALS['gEnv']['site']['db']->GetDateArrayFromTimestamp(
                    $inv_data['duedate']
                    ) ) );
        $template->Register( 'invoice', 'tpl_invoice_paymenttype', $payment->GetDescription() );
        $template->Register( 'invoice', 'tpl_invoice_amount',
            number_format(
                $inv_data['amount'],
                $locale_country->FractDigits(),
                $locale_country->MoneyDecimalSeparator(),
                $locale_country->MoneyThousandsSeparator()
                )
            );
        $template->Register( 'invoice', 'tpl_invoice_vat',
            number_format(
                $inv_data['vat'],
                $locale_country->FractDigits(),
                $locale_country->MoneyDecimalSeparator(),
                $locale_country->MoneyThousandsSeparator()
                )
            );
        $template->Register( 'invoice', 'tpl_invoice_total',
            number_format(
                $inv_data['total'],
                $locale_country->FractDigits(),
                $locale_country->MoneyDecimalSeparator(),
                $locale_country->MoneyThousandsSeparator()
                )
            );

        // Customer data

        $xen_company = new XenDirectoryCompany(
            $GLOBALS['gEnv']['root']['db'],
            $GLOBALS['gEnv']['site']['db'],
            $inv_data['customerid']
            );

        $cust_data = $xen_company->GetItem();

        $template->Register( 'invoice', 'tpl_invoice_customer_code', $cust_data['code'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_name', $cust_data['companyname'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_street', $cust_data['street'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_city', $cust_data['city'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_zip', $cust_data['zip'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_state', $cust_data['state'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_country', $cust_data['country'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_phone', $cust_data['phone'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_fax', $cust_data['fax'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_email', $cust_data['email'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_url', $cust_data['url'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_fiscalcode', $cust_data['fiscalcode'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_fiscalcodeb', $cust_data['fiscalcodeb'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_account_manager', $cust_data['accountmanager'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_firstname', $cust_data['firstname'] );
        $template->Register( 'invoice', 'tpl_invoice_customer_lastname', $cust_data['lastname'] );

        // Project data

        $xen_project = new XenProject(
            $GLOBALS['gEnv']['root']['db'],
            $GLOBALS['gEnv']['site']['db'],
            $inv_data['projectid']
            );

        $project_data = $xen_project->GetItem();

        $template->Register( 'invoice', 'tpl_invoice_project_name', $project_data['name'] );

        $template->Parse( 'invoice' );
        unset( $inv_rows['amount'] );
        $template->Parse_Loop( 'invoice', 'rows', $inv_rows );

        return $template->Return_File( 'invoice' );
    }

    function SendToEmail(
        $email = ''
        )
    {
        $result = false;

        if ( $this->mItemId )
        {
            OpenLibrary( 'modules.library' );

            $mod_deps = new ModuleDep(
                $GLOBALS['gEnv']['root']['db']
                );
            if (
                $mod_deps->IsInstalled( 'htmlmimemail' )
                and
                $mod_deps->IsInstalled( 'smtpsend' )
                )
            {
                $inv_data = $this->GetItem();

                OpenLibrary( 'xendirectory.library' );

                if (
                    !strlen( $email )
                    and
                    isset( $inv_data['customerid'] )
                    )
                {
                    $xen_customer = new XenDirectoryCompany(
                        $GLOBALS['gEnv']['root']['db'],
                        $GLOBALS['gEnv']['site']['db'],
                        $inv_data['customerid']
                        );

                    $cust_data = $xen_customer->GetItem();

                    if (
                        isset( $cust_data['email'] )
                        and
                        strlen( $cust_data['email'] )
                        ) $email = $cust_data['email'];
                }

                if ( strlen( $email ) )
                {
                    OpenLibrary( 'htmlmimemail.library' );
                    OpenLibrary( 'smtpsend.library' );
                    OpenLibrary( 'locale.library' );

                    $sets = new XenBilling_SettingsHandler();

                    $locale = new Locale(
                        'xenbilling_misc',
                        $GLOBALS['gEnv']['site']['locale']['language']
                        );

                    $tmp_smtp = $sets->GetSmtpServer();

                    $smtp = new smtp_class();
                    $smtp->localhost = 'localhost';
                    $smtp->host_name = strlen( $tmp_smtp ) ? $tmp_smtp : 'localhost';
                    $smtp->port = 25;
                    $smtp->SetRecipient( $email );

                    $mail = new html_mime_mail( 'X-Mailer: XenBilling' );
                    $html = $this->CreateHtmlInvoice();
                    $mail->add_html( $html, '', '' );
                    $mail->set_charset( 'iso-8859-1' );
                    $mail->build_message();

                    $addresses[] = $email;
                    $headers[0] = sprintf(
                        'Subject: '.$locale->GetStr( 'invoice_email_subject' ),
                        $inv_data['number']
                        );
                    $headers[1] = 'From: '.$sets->GetEmail();

                    $mail->smtp_send(
                        $smtp,
                        '',
                        $addresses,
                        $headers
                        );

                    $result = true;
                }
            }
        }

        return $result;
    }
}

class XenBilling_Vat
{
    var $mId = 0;
    var $mDescription;
    var $mPercentual;

    function XenBilling_Vat(
        $id = 0
        )
    {
        $id = (int)$id;

        if ( $id )
        {
            $check_query = &$GLOBALS['gEnv']['site']['db']->Execute(
                'SELECT * '.
                'FROM innowork_billing_vat_codes '.
                'WHERE id='.$id
                );

            if ( $check_query->NumRows() )
            {
                $this->mId = $id;
                $this->mDescription = $check_query->Fields( 'vat' );
                $this->mPercentual = $check_query->Fields( 'percentual' );

                $check_query->Free();
            }
        }
    }

    function Create(
        $description,
        $percentual
        )
    {
        $result = false;

        if ( !$this->mId )
        {
            $id = $GLOBALS['gEnv']['site']['db']->NextSeqValue( 'innowork_billing_vat_codes_id_seq' );

            if ( $GLOBALS['gEnv']['site']['db']->Execute(
                'INSERT INTO innowork_billing_vat_codes VALUES ('.
                $id.','.
                $GLOBALS['gEnv']['site']['db']->Format_Text( $description ).','.
                $GLOBALS['gEnv']['site']['db']->Format_Text( $percentual ).')' ) )
            {
                $this->mId = $id;
                $this->mDescription = $description;
                $this->mPercentual = $percentual;

                $result = true;
            }
        }

        return $result;
    }

    function GetDescription()
    {
        return $this->mDescription;
    }

    function SetDescription(
        $description
        )
    {
        $result = false;

        if (
            $this->mId
            and
            $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_vat_codes '.
                'SET vat='.$GLOBALS['gEnv']['site']['db']->Format_Text( $description ).' '.
                'WHERE id='.$this->mId
                )
            )
        {
            $this->mDescription = $description;

            $result = true;
        }

        return $result;
    }

    function GetPercentual()
    {
        return $this->mPercentual;
    }

    function SetPercentual(
        $percentual
        )
    {
        $result = false;

        if (
            $this->mId
            and
            $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_vat_codes '.
                'SET percentual='.$GLOBALS['gEnv']['site']['db']->Format_Text( $percentual ).' '.
                'WHERE id='.$this->mId
                )
            )
        {
            $this->mPercentual = $percentual;

            $result = true;
        }

        return $result;
    }

    function Remove()
    {
        $result = false;

        if (
            $this->mId
            and
            $GLOBALS['gEnv']['site']['db']->Execute(
                'DELETE FROM innowork_billing_vat_codes '.
                'WHERE id='.$this->mId
                )
            )
        {
            $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_invoices_rows '.
                'SET vatid=0 '.
                'WHERE vatid='.$this->mId
                );

            $sets = new XenBilling_SettingsHandler();

            if ( $sets->GetDefaultVat() == $this->mId )
            {
                $sets->SetDefaultVat( '0' );
            }

            $this->mId = 0;
            $this->mDescription = $this->mPercentual = '';

            $result = true;
        }

        return $result;
    }
}

class XenBilling_Payment
{
    var $mId = 0;
    var $mDescription;
    var $mDays = 0;
    var $mMonthEnd = false;

    function XenBilling_Payment(
        $id = 0
        )
    {
        $id = (int)$id;

        if ( $id )
        {
            $check_query = &$GLOBALS['gEnv']['site']['db']->Execute(
                'SELECT * '.
                'FROM innowork_billing_payments '.
                'WHERE id='.$id
                );

            if ( $check_query->NumRows() )
            {
                $this->mId = $id;
                $this->mDescription = $check_query->Fields( 'description' );
                $this->mDays = $check_query->Fields( 'days' );
                $this->mMonthEnd = $check_query->Fields( 'monthend' ) == $GLOBALS['gEnv']['site']['db']->fmttrue ? true : false;

                $check_query->Free();
            }
        }
    }

    function Create(
        $description,
        $days,
        $monthEnd
        )
    {
        $result = false;

        if ( !$this->mId )
        {
            $id = $GLOBALS['gEnv']['site']['db']->NextSeqValue( 'innowork_billing_payments_id_seq' );
            $days = (int)$days;
            if ( !strlen( $days ) ) $days = 0;

            if ( $GLOBALS['gEnv']['site']['db']->Execute(
                'INSERT INTO innowork_billing_payments VALUES ('.
                $id.','.
                $GLOBALS['gEnv']['site']['db']->Format_Text( $description ).','.
                $days.','.
                $GLOBALS['gEnv']['site']['db']->Format_Text(
                    $monthEnd ?
                    $GLOBALS['gEnv']['site']['db']->fmttrue :
                    $GLOBALS['gEnv']['site']['db']->fmtfalse
                    ).')' ) )
            {
                $this->mId = $id;
                $this->mDescription = $description;
                $this->mDays = $days;
                $this->mMonthEnd = $monthEnd;

                $result = true;
            }
        }

        return $result;
    }

    function GetDescription()
    {
        return $this->mDescription;
    }

    function SetDescription(
        $description
        )
    {
        $result = false;

        if (
            $this->mId
            and
            $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_payments '.
                'SET description='.$GLOBALS['gEnv']['site']['db']->Format_Text( $description ).' '.
                'WHERE id='.$this->mId
                )
            )
        {
            $this->mDescription = $description;

            $result = true;
        }

        return $result;
    }

    function GetDays()
    {
        return $this->mDays;
    }

    function SetDays(
        $days
        )
    {
        $result = false;

        $days = (int)$days;
        if ( !strlen( $days ) ) $days = 0;

        if (
            $this->mId
            and
            $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_payments '.
                'SET days='.$days.' '.
                'WHERE id='.$this->mId
                )
            )
        {
            $this->mDays = $days;

            $result = true;
        }

        return $result;
    }

    function GetMonthEnd()
    {
        return $this->mMonthEnd;
    }

    function SetMonthEnd(
        $monthEnd
        )
    {
        $result = false;

        if (
            $this->mId
            and
            $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_payments '.
                'SET monthend='.(
                    $monthEnd ?
                    $GLOBALS['gEnv']['site']['db']->fmttrue :
                    $GLOBALS['gEnv']['site']['db']->fmtfalse
                    ).' '.
                'WHERE id='.$this->mId
                )
            )
        {
            $this->mMonthEnd = $monthEnd;

            $result = true;
        }

        return $result;
    }

    function Remove()
    {
        $result = false;

        if (
            $this->mId
            and
            $GLOBALS['gEnv']['site']['db']->Execute(
                'DELETE FROM innowork_billing_payments '.
                'WHERE id='.$this->mId
                )
            )
        {
            $GLOBALS['gEnv']['site']['db']->Execute(
                'UPDATE innowork_billing_invoices_rows '.
                'SET paymentid=0 '.
                'WHERE paymentid='.$this->mId
                );

            $sets = new XenBilling_SettingsHandler();

            if ( $sets->GetDefaultPayment() == $this->mId )
            {
                $sets->SetDefaultPayment( '0' );
            }

            $this->mId = 0;
            $this->mDescription = '';
            $this->mDays = 0;
            $this->mMonthEnd = false;

            $result = true;
        }

        return $result;
    }
}

class XenBilling_SettingsHandler
{
    function GetDefaultVat()
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->GetKey( 'xenbilling-default-vat' );
    }

    function SetDefaultVat(
        $defaultVat
        )
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->SetKey( 'xenbilling-default-vat', $defaultVat );
    }

    function GetDefaultPayment()
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->GetKey( 'xenbilling-default-payment' );
    }

    function SetDefaultPayment(
        $defaultPayment
        )
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->SetKey( 'xenbilling-default-payment', $defaultPayment );
    }

    function GetInvoiceTemplate()
    {
        $result = '';

        $file_name = SITESTUFF_PATH.$GLOBALS['gEnv']['site']['id'].'/etc/xenbilling_invoice.html';

        if (
            file_exists( $file_name )
            and
            $fp = fopen( $file_name, 'r' )
            )
        {
            $result = fread( $fp, filesize( $file_name ) );
            fclose( $fh );
        }
        else
        {
            OpenLibrary( 'locale.library' );

            $locale = new Locale(
                'xenbilling_misc',
                $GLOBALS['gEnv']['user']['locale']['language']
                );

            $result = $locale->GetStr( 'notemplate_set' );
        }

        return $result;
    }

    function SetInvoiceTemplate(
        $invoiceTemplateContent
        )
    {
        $result = false;

        $file_name = SITESTUFF_PATH.$GLOBALS['gEnv']['site']['id'].'/etc/xenbilling_invoice.html';

        if ( $fp = fopen( $file_name, 'w' ) )
        {
            $result = fwrite( $fp, $invoiceTemplateContent );
        }

        return $result;
    }

    function GetNotifiesEmail()
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->GetKey( 'xenbilling-notifies-email' );
    }

    function SetNotifiesEmail(
        $email
        )
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->SetKey( 'xenbilling-notifies-email', $email );
    }

    function GetEmail()
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->GetKey( 'xenbilling-email' );
    }

    function SetEmail(
        $email
        )
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->SetKey( 'xenbilling-email', $email );
    }

    function GetSmtpServer()
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->GetKey( 'xenbilling-smtp-server' );
    }

    function SetSmtpServer(
        $server
        )
    {
        OpenLibrary( 'sites.library' );
        $sets = new SiteSettings( $GLOBALS['gEnv']['site']['db'] );
        return $sets->SetKey( 'xenbilling-smtp-server', $server );
    }
}

?>
