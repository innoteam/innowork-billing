<?php
namespace Innowork\Billing;

require_once ('innowork/core/InnoworkItem.php');

class InnoworkInvoice extends \Innowork\Core\InnoworkItem
{

    var $mTable = 'innowork_billing_invoices';

    var $mNewDispatcher = 'view';

    var $mNewEvent = 'newinvoice';

    var $mNoAcl = true;

    var $mTypeTags = array(
        'invoice'
    );
    // var $mNoLog = true;
    var $mNoTrash = false;

    var $mConvertible = true;

    const ITEM_TYPE = 'invoice';

    public function __construct($rampDb, $rsiteDb, $invoiceId = 0)
    {
        parent::__construct($rampDb, $rsiteDb, self::ITEM_TYPE, $invoiceId);
        
        $this->mKeys['number'] = 'text';
        $this->mKeys['customerid'] = 'table:innowork_directory_companies:companyname:integer';
        $this->mKeys['projectid'] = 'table:innowork_projects:name:integer';
        $this->mKeys['emissiondate'] = 'timestamp';
        $this->mKeys['duedate'] = 'timestamp';
        $this->mKeys['amount'] = 'decimal';
        $this->mKeys['total'] = 'decimal';
        $this->mKeys['paidamount'] = 'decimal';
        $this->mKeys['vat'] = 'decimal';
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

    function doCreate($params, $userId)
    {
        $result = false;
        
        if (! isset($params['projectid']) or ! strlen($params['projectid']))
            $params['projectid'] = '0';
        
        if (! isset($params['customerid']) or ! strlen($params['customerid']))
            $params['customerid'] = '0';
        
        if (count($params)) {
            $item_id = $this->mrDomainDA->getNextSequenceValue($this->mTable . '_id_seq');
            
            $key_pre = $value_pre = $keys = $values = '';
            
            $country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
            
            while (list ($key, $val) = each($params)) {
                $key_pre = ',';
                $value_pre = ',';
                
                switch ($key) {
                    case 'number':
                    case 'amount':
                    case 'vat':
                    case 'total':
                    case 'paidamount':
                    case 'accountmanager':
                        $keys .= $key_pre . $key;
                        $values .= $value_pre . $this->mrDomainDA->formatText($val);
                        break;
                    
                    case 'emissiondate':
                    case 'duedate':
                        $date_array = $country->GetDateArrayFromShortDateStamp($val);
                        $val = $this->mrDomainDA->GetTimestampFromDateArray($date_array);
                        unset($date_array);
                        
                        $keys .= $key_pre . $key;
                        $values .= $value_pre . $this->mrDomainDA->formatText($val);
                        break;
                    
                    case 'customerid':
                    case 'projectid':
                    case 'paymentid':
                        if (! strlen($key))
                            $key = 0;
                        $keys .= $key_pre . $key;
                        $values .= $value_pre . $val;
                        break;
                    
                    default:
                        break;
                }
            }
            
            if (strlen($values)) {
                if ($this->mrDomainDA->Execute('INSERT INTO ' . $this->mTable . ' ' . '(id,ownerid' . $keys . ') ' . 'VALUES (' . $item_id . ',' . $userId . $values . ')')) {
                    $this->SetLastInvoiceNumber($params['number']);
                    
                    $result = $item_id;
                }
            }
        }
        
        $this->_mCreationAcl = XENCORE_ACL_TYPE_PUBLIC;
        
        return $result;
    }

    function doEdit($params)
    {
        $result = false;
        
        if ($this->mItemId) {
            if (count($params)) {
                $start = 1;
                $update_str = '';
                
                $country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
                
                while (list ($field, $value) = each($params)) {
                    if ($field != 'id') {
                        switch ($field) {
                            case 'number':
                            case 'amount':
                            case 'vat':
                            case 'total':
                            case 'paidamount':
                            case 'accountmanager':
                                if (! $start)
                                    $update_str .= ',';
                                $update_str .= $field . '=' . $this->mrDomainDA->formatText($value);
                                $start = 0;
                                break;
                            
                            case 'emissiondate':
                            case 'duedate':
                                $date_array = $country->GetDateArrayFromShortDateStamp($value);
                                $value = $this->mrDomainDA->GetTimestampFromDateArray($date_array);
                                unset($date_array);
                                
                                if (! $start)
                                    $update_str .= ',';
                                $update_str .= $field . '=' . $this->mrDomainDA->formatText($value);
                                $start = 0;
                                break;
                            
                            case 'customerid':
                            case 'projectid':
                            case 'paymentid':
                                if (! strlen($value))
                                    $value = 0;
                                if (! $start)
                                    $update_str .= ',';
                                $update_str .= $field . '=' . $value;
                                $start = 0;
                                break;
                            
                            default:
                                break;
                        }
                    }
                }
                
                $query = $this->mrDomainDA->Execute('UPDATE ' . $this->mTable . ' ' . 'SET ' . $update_str . ' ' . 'WHERE id=' . $this->mItemId);
                
                if ($query)
                    $result = TRUE;
            }
        }
        
        return $result;
    }

    function doRemove($userId)
    {
        $result = FALSE;
        
        $result = $this->mrDomainDA->Execute('DELETE FROM ' . $this->mTable . ' ' . 'WHERE id=' . $this->mItemId);
        
        if ($result) {
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('DELETE FROM innowork_billing_invoices_rows ' . 'WHERE invoiceid=' . $this->mItemId);
        }
        
        return $result;
    }

    function doGetItem($userId)
    {
        $result = FALSE;
        
        $item_query = $this->mrDomainDA->Execute('SELECT * ' . 'FROM ' . $this->mTable . ' ' . 'WHERE id=' . $this->mItemId);
        
        if (is_object($item_query) and $item_query->getNumberRows()) {
            $result = $item_query->getFields();
        }
        
        return $result;
    }

    public function addRow($description, $amount, $vatId, $quantity, $discount, $rowCounter = '')
    {
        $result = false;
        
        $vatId = (int) $vatId;
        if (! strlen($vatId))
            $vatId = 0;
        
        if ($this->mItemId) {
            $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry());
            
            $amount = str_replace(',', '.', $amount);
            $amount = number_format($amount, $locale_country->FractDigits(), '.', '');
            
            $quantity = str_replace(',', '.', $quantity);
            
            $id = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->getNextSequenceValue('innowork_billing_invoices_rows_id_seq');
            
            if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('INSERT INTO innowork_billing_invoices_rows (id, invoiceid, description, amount, quantity, discount, vatid, rowcounter) ' . 'VALUES (' . $id . ',' . $this->mItemId . ',' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($description) . ',' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($amount) . ',' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($quantity) . ',' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText((int) $discount) . ',' . $vatId . ',' . $rowCounter . ')')) {
                $this->setInvoiceTotals($this->calculateInvoiceTotals());
                
                $result = true;
            }
        }
        
        return $result;
    }

    public function getRow($rowId)
    {
        $result = array();
        
        $rowId = (int) $rowId;
        if (! strlen($rowId))
            $rowId = 0;
        
        if ($this->mItemId and $rowId) {
            $query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('SELECT * ' . 'FROM innowork_billing_invoices_rows ' . 'WHERE invoiceid=' . $this->mItemId . ' ' . 'AND id=' . $rowId);
            
            if ($query->getNumberRows()) {
                $result['id'] = $rowId;
                $result['invoiceid'] = $query->getFields('invoiceid');
                $result['description'] = $query->getFields('description');
                $result['vatid'] = $query->getFields('vatid');
                $result['amount'] = $query->getFields('amount');
                $result['quantity'] = $query->getFields('quantity');
                $result['discount'] = $query->getFields('discount');
                $result['rowcounter'] = $query->getFields('rowcounter');
            }
        }
        
        return $result;
    }

    public function editRow($rowId, $description, $amount, $vatId, $quantity, $discount)
    {
        $result = false;
        
        $vatId = (int) $vatId;
        if (! strlen($vatId))
            $vatId = 0;
        $rowId = (int) $rowId;
        if (! strlen($rowId))
            $rowId = 0;
        
        if ($this->mItemId and $rowId) {
            $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry());
            
            $amount = str_replace(',', '.', $amount);
            $amount = number_format($amount, $locale_country->FractDigits(), '.', '');
            
            $quantity = str_replace(',', '.', $quantity);
            
            $old_row = $this->GetRow($rowId);
            
            if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('UPDATE innowork_billing_invoices_rows SET ' . 'description=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($description) . ',' . 'amount=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($amount) . ',' . 'quantity=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($quantity) . ',' . 'discount=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText((int) $discount) . ',' . 'vatid=' . $vatId . ' ' . 'WHERE id=' . $rowId . ' ' . 'AND invoiceid=' . $this->mItemId)) {
                $this->SetInvoiceTotals($this->CalculateInvoiceTotals());
                
                $result = true;
            }
        }
        
        return $result;
    }

    public function removeRow($rowId)
    {
        $result = false;
        
        $rowId = (int) $rowId;
        if (! strlen($rowId))
            $rowId = 0;
        
        if ($this->mItemId and $rowId) {
            if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('DELETE FROM innowork_billing_invoices_rows ' . 'WHERE invoiceid=' . $this->mItemId . ' ' . 'AND id=' . $rowId)) {
                $this->SetInvoiceTotals($this->CalculateInvoiceTotals());
                
                $result = true;
            }
        }
        
        return $result;
    }

    public function getRows()
    {
        $result = array();
        
        if ($this->mItemId) {
            $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry());
            
            $rows_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('SELECT * ' . 'FROM innowork_billing_invoices_rows ' . 'WHERE invoiceid=' . $this->mItemId . ' ' . 'ORDER BY id');
            
            $vats_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('SELECT id,vat,percentual,description ' . 'FROM innowork_billing_vat_codes ');
            
            $vats = array();
            while (! $vats_query->eof) {
                $vats[$vats_query->getFields('id')] = array(
                    'percentual' => $vats_query->getFields('percentual'),
                    'name' => $vats_query->getFields('vat'),
                    'description' => $vats_query->getFields('description')
                );
                $vats_query->MoveNext();
            }
            
            while (! $rows_query->eof) {
                $vat = 0;
                $quantity = $rows_query->getFields('quantity');
                if (! (int) $quantity)
                    $quantity = 1;
                
                $result['amount'] += $tmp_row_amount = (($rows_query->getFields('amount') - ($rows_query->getFields('amount') * $rows_query->getFields('discount') / 100)) * $quantity);
                
                if ($rows_query->getFields('vatid') != 0 and isset($vats[$rows_query->getFields('vatid')])) {
                    $vat = round($tmp_row_amount * $vats[$rows_query->getFields('vatid')]['percentual'] / 100, $locale_country->fractDigits());
                    
                    $vat_name = $vats[$rows_query->getFields('vatid')]['name'];
                    $vat_description = $vats[$rows_query->getFields('vatid')]['description'];
                    $vat_percentual = $vats[$rows_query->getFields('vatid')]['percentual'];
                } else {
                    // No name and description
                    $vat_name = $vat_description = '';
                    $vat_percentual = 0;
                }
                
                $result[] = array(
                    'id' => $rows_query->getFields('id'),
                    'description' => $rows_query->getFields('description'),
                    'amount' => number_format($rows_query->getFields('amount'), $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator()),
                    'totalamount' => number_format($tmp_row_amount, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator()),
                    'quantity' => $quantity,
                    'discount' => $rows_query->getFields('discount'),
                    'vatid' => $rows_query->getFields('vatid'),
                    'vatname' => $vat_name,
                    'vatdescription' => $vat_description,
                    'vatpercentual' => $vat_percentual,
                    'vat' => number_format($vat, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator()),
                    'total' => number_format($tmp_row_amount + $vat, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator()),
                    'unf_amount' => number_format($rows_query->getFields('amount'), $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), ''),
                    'unf_totalamount' => number_format($tmp_row_amount, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), ''),
                    'unf_vat' => number_format($vat, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), ''),
                    'unf_total' => number_format($tmp_row_amount + $vat, $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), '')
                );
                
                $rows_query->MoveNext();
            }
        }
        
        return $result;
    }

    public function getVats()
    {
        if (! $this->mItemId) {
            return array();
        }
        
        $result = array();
        
        $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry());
        
        $rows_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->Execute('SELECT * ' . 'FROM innowork_billing_invoices_rows ' . 'WHERE invoiceid=' . $this->mItemId . ' ' . 'ORDER BY id');
        
        $vats_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->execute('SELECT co.* FROM innowork_billing_vat_codes AS co JOIN innowork_billing_invoices_rows AS ro on ro.vatid=co.id GROUP BY co.id ORDER BY co.vat');
        
        $vats = array();
        $row = 0;
        while (! $vats_query->eof) {
            $vats[$vats_query->getFields('id')] = array(
                'percentual' => $vats_query->getFields('percentual'),
                'name' => $vats_query->getFields('vat'),
                'description' => $vats_query->getFields('description'),
                'row' => $row ++
            );
            $vats_query->MoveNext();
        }
        
        while (! $rows_query->eof) {
            $vat = 0;
            $quantity = $rows_query->getFields('quantity');
            if (! (int) $quantity)
                $quantity = 1;
            
            $tmp_row_amount = (($rows_query->getFields('amount') - ($rows_query->getFields('amount') * $rows_query->getFields('discount') / 100)) * $quantity);
            
            if ($rows_query->getFields('vatid') != 0 and isset($vats[$rows_query->getFields('vatid')])) {
                $vat = round($tmp_row_amount * $vats[$rows_query->getFields('vatid')]['percentual'] / 100, $locale_country->fractDigits());
            }
            
            if (isset($result[$vats[$rows_query->getFields('vatid')]['row']])) {
                $result[$vats[$rows_query->getFields('vatid')]['row']]['totalamount'] += $tmp_row_amount;
                $result[$vats[$rows_query->getFields('vatid')]['row']]['vat'] += $vat;
                $result[$vats[$rows_query->getFields('vatid')]['row']]['total'] += $tmp_row_amount + $vat;
            } else {
                $result[$vats[$rows_query->getFields('vatid')]['row']] = array(
                    'totalamount' => $tmp_row_amount,
                    'vat' => $vat,
                    'total' => $tmp_row_amount + $vat,
                    'vatname' => $vats[$rows_query->getFields('vatid')]['name'],
                    'vatdescription' => $vats[$rows_query->getFields('vatid')]['description'],
                    'vatpercentual' => $vats[$rows_query->getFields('vatid')]['percentual']
                );
            }
            
            $rows_query->moveNext();
        }
        
        foreach ($result as $id => $result_row) {
            $result[$id]['unf_totalamount'] = number_format($result[$id]['totalamount'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), '');
            $result[$id]['unf_vat'] = number_format($result[$id]['vat'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), '');
            $result[$id]['unf_total'] = number_format($result[$id]['total'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), '');
            $result[$id]['totalamount'] = number_format($result[$id]['totalamount'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator());
            $result[$id]['vat'] = number_format($result[$id]['vat'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator());
            $result[$id]['total'] = number_format($result[$id]['total'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator());
        }
        
        return $result;
    }

    public function calculateInvoiceTotals()
    {
        $result = array();
        
        if ($this->mItemId) {
            $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry());
            
            $result['amount'] = $result['vat'] = $result['total'] = 0;
            
            $vats = array();
            
            $vats_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('SELECT id,percentual ' . 'FROM innowork_billing_vat_codes');
            
            while (! $vats_query->eof) {
                $vats[$vats_query->getFields('id')] = $vats_query->getFields('percentual');
                
                $vats_query->MoveNext();
            }
            
            $rows_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('SELECT amount,quantity,discount,vatid ' . 'FROM innowork_billing_invoices_rows ' . 'WHERE invoiceid=' . $this->mItemId);
            
            while (! $rows_query->eof) {
                $result['amount'] += $tmp_row_amount = (($rows_query->getFields('amount') - ($rows_query->getFields('amount') * $rows_query->getFields('discount') / 100)) * $rows_query->getFields('quantity'));
                
                if ($rows_query->getFields('vatid') and isset($vats[$rows_query->getFields('vatid')])) {
                    $result['vat'] += round($tmp_row_amount * $vats[$rows_query->getFields('vatid')] / 100, $locale_country->FractDigits());
                }
                
                $rows_query->MoveNext();
            }
            
            $result['amount'] = number_format($result['amount'], $locale_country->FractDigits(), '.', '');
            
            $result['vat'] = number_format($result['vat'], $locale_country->FractDigits(), '.', '');
            
            $result['total'] = number_format($result['amount'] + $result['vat'], $locale_country->FractDigits(), '.', '');
        }
        
        return $result;
    }

    public function getInvoiceTotals()
    {
        $result = array();
        
        if ($this->mItemId) {
            $query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('SELECT amount,vat,total ' . 'FROM innowork_billing_invoices ' . 'WHERE id=' . $this->mItemId);
            
            if ($query->getNumberRows()) {
                $result['amount'] = $query->getFields('amount');
                $result['vat'] = $query->getFields('vat');
                $result['total'] = $query->getFields('total');
            }
        }
        
        return $result;
    }

    public function setInvoiceTotals($totals)
    {
        $result = false;
        
        if ($this->mItemId) {
            if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('UPDATE innowork_billing_invoices ' . 'SET amount=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($totals['amount']) . ',' . 'vat=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($totals['vat']) . ',' . 'total=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($totals['total']) . ' ' . 'WHERE id=' . $this->mItemId)) {
                $this->cleanCache();
                $result = true;
            }
        }
        
        return $result;
    }

    public function setPaidAmount($amount)
    {
        $result = false;
        
        if ($this->mItemId) {
            $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry());
            
            $amount = str_replace(',', '.', $amount);
            
            $amount = number_format($amount, $locale_country->FractDigits(), '.', '');
            
            if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('UPDATE innowork_billing_invoices ' . 'SET paidamount=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->formatText($amount) . ' ' . 'WHERE id=' . $this->mItemId)) {
                $this->cleanCache();
                $result = true;
            }
        }
        
        return $result;
    }

    public function setLastInvoiceNumber($number)
    {
        require_once ('innomatic/domain/DomainSettings.php');
        
        $site_sets = new DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        $site_sets->SetKey('xen-billing-lastinvoicenumber', $number);
        
        return true;
    }

    public static function getLastInvoiceNumber()
    {
        require_once ('innomatic/domain/DomainSettings.php');
        
        $site_sets = new DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        $result = $site_sets->GetKey('xen-billing-lastinvoicenumber');
        
        if (! strlen($result))
            $result = 0;
        
        return $result;
    }
    
    public static function getLastInvoiceDate()
    {
        $domain_da = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess();
        $date_query = $domain_da->execute('SELECT MAX( emissiondate ) AS emissiondate FROM innowork_billing_invoices');
        $date = $date_query->getFields('emissiondate');
        return $date;
    }

    public function createHtmlInvoice()
    {
        require_once 'rhtemplate/RHTemplate.php';
        require_once ('innowork/projects/InnoworkProject.php');
        require_once ('innowork/groupware/InnoworkCompany.php');
        
        $locale_country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry());
        
        $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
        
        $template = new Rh_Template();
        $template->files['invoice'] = $sets->GetInvoiceTemplate();
        
        unset($sets);
        
        // Invoice data
        
        $inv_data = $this->getItem();
        $inv_rows = $this->getRows();
        $inv_vats = $this->getVats();
        
        $payment = new \Innowork\Billing\InnoworkBillingPayment($inv_data['paymentid']);
        
        $template->Register('invoice', 'tpl_invoice_number', $inv_data['number']);
        $template->Register('invoice', 'tpl_invoice_emissiondate', $locale_country->FormatShortArrayDate(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->GetDateArrayFromTimestamp($inv_data['emissiondate'])));
        $template->Register('invoice', 'tpl_invoice_duedate', $locale_country->FormatShortArrayDate(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->GetDateArrayFromTimestamp($inv_data['duedate'])));
        $template->Register('invoice', 'tpl_invoice_paymenttype', $payment->GetDescription());
        $template->Register('invoice', 'tpl_invoice_amount', number_format($inv_data['amount'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator()));
        $template->Register('invoice', 'tpl_invoice_vat', number_format($inv_data['vat'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator()));
        $template->Register('invoice', 'tpl_invoice_total', number_format($inv_data['total'], $locale_country->FractDigits(), $locale_country->MoneyDecimalSeparator(), $locale_country->MoneyThousandsSeparator()));
        
        // Customer data
        
        $xen_company = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $inv_data['customerid']);
        
        $cust_data = $xen_company->GetItem();
        
        $template->Register('invoice', 'tpl_invoice_customer_code', $cust_data['code']);
        $template->Register('invoice', 'tpl_invoice_customer_name', $cust_data['companyname']);
        $template->Register('invoice', 'tpl_invoice_customer_street', $cust_data['street']);
        $template->Register('invoice', 'tpl_invoice_customer_city', $cust_data['city']);
        $template->Register('invoice', 'tpl_invoice_customer_zip', $cust_data['zip']);
        $template->Register('invoice', 'tpl_invoice_customer_state', $cust_data['state']);
        $template->Register('invoice', 'tpl_invoice_customer_country', $cust_data['country']);
        $template->Register('invoice', 'tpl_invoice_customer_phone', $cust_data['phone']);
        $template->Register('invoice', 'tpl_invoice_customer_fax', $cust_data['fax']);
        $template->Register('invoice', 'tpl_invoice_customer_email', $cust_data['email']);
        $template->Register('invoice', 'tpl_invoice_customer_url', $cust_data['url']);
        $template->Register('invoice', 'tpl_invoice_customer_fiscalcode', $cust_data['fiscalcode']);
        $template->Register('invoice', 'tpl_invoice_customer_fiscalcodeb', $cust_data['fiscalcodeb']);
        $template->Register('invoice', 'tpl_invoice_customer_account_manager', $cust_data['accountmanager']);
        $template->Register('invoice', 'tpl_invoice_customer_firstname', $cust_data['firstname']);
        $template->Register('invoice', 'tpl_invoice_customer_lastname', $cust_data['lastname']);
        
        // Project data
        
        $xen_project = new InnoworkProject(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $inv_data['projectid']);
        
        $project_data = $xen_project->GetItem();
        
        $template->Register('invoice', 'tpl_invoice_project_name', $project_data['name']);
        
        $template->Parse('invoice');
        unset($inv_rows['amount']);
        $template->Parse_Loop('invoice', 'vats', $inv_vats);
        $template->Parse_Loop('invoice', 'rows', $inv_rows);
        
        return $template->Return_File('invoice');
    }

    public function sendToEmail($email = '')
    {
        $result = false;
        
        if ($this->mItemId) {
            $mod_deps = new \Innomatic\Application\ApplicationDependencies(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess());
            
            if ($mod_deps->isInstalled('htmlmimemail') and $mod_deps->isInstalled('smtpsend')) {
                $inv_data = $this->GetItem();
                
                require_once 'innowork/groupware/InnoworkCompany.php';
                
                if (! strlen($email) and isset($inv_data['customerid'])) {
                    $xen_customer = new InnoworkCompany(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $inv_data['customerid']);
                    
                    $cust_data = $xen_customer->GetItem();
                    
                    if (isset($cust_data['email']) and strlen($cust_data['email']))
                        $email = $cust_data['email'];
                }
                
                if (strlen($email)) {
                    require_once 'htmlmimemail/HTMLMimeMail.php';
                    require_once 'smtpsend/SMTPSend.php';
                    
                    $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
                    
                    $locale = new \Innomatic\Locale\LocaleCatalog('innowork-billing::misc', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getLanguage());
                    
                    $tmp_smtp = $sets->GetSmtpServer();
                    
                    $smtp = new smtp_class();
                    $smtp->localhost = 'localhost';
                    $smtp->host_name = strlen($tmp_smtp) ? $tmp_smtp : 'localhost';
                    $smtp->port = 25;
                    $smtp->SetRecipient($email);
                    
                    $mail = new html_mime_mail('X-Mailer: InnoworkBilling');
                    $html = $this->CreateHtmlInvoice();
                    $mail->add_html($html, '', '');
                    $mail->set_charset('iso-8859-1');
                    $mail->build_message();
                    
                    $addresses[] = $email;
                    $headers[0] = sprintf('Subject: ' . $locale->GetStr('invoice_email_subject'), $inv_data['number']);
                    $headers[1] = 'From: ' . $sets->GetEmail();
                    
                    $mail->smtp_send($smtp, '', $addresses, $headers);
                    
                    $result = true;
                }
            }
        }
        
        return $result;
    }
}
