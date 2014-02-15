<?php
namespace Innowork\Billing;

class InnoworkBillingVat
{

    var $mId = 0;

    var $mName;

    var $mDescription;

    var $mPercentual;

    public function __construct($id = 0)
    {
        $id = (int) $id;
        
        if ($id) {
            $check_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->execute('SELECT * ' . 'FROM innowork_billing_vat_codes ' . 'WHERE id=' . $id);
            
            if ($check_query->getNumberRows()) {
                $this->mId = $id;
                $this->mName = $check_query->getFields('name');
                $this->mDescription = $check_query->getFields('description');
                $this->mPercentual = $check_query->getFields('percentual');
                
                $check_query->Free();
            }
        }
    }

    public function create($name, $percentual, $description)
    {
        $result = false;
        
        if (! $this->mId) {
            $domain_da = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess();
            
            $id = $domain_da->getNextSequenceValue('innowork_billing_vat_codes_id_seq');
            
            if ($domain_da->Execute('INSERT INTO innowork_billing_vat_codes VALUES (' . $id . ',' . 
                $domain_da->formatText($name) . ','.
                $domain_da->formatText($percentual) . ',' . $domain_da->formatText($description) . ')')) {
                $this->mId = $id;
                $this->mName = $name;
                $this->mDescription = $description;
                $this->mPercentual = $percentual;
                
                $result = true;
            }
        }
        
        return $result;
    }

    public function getName()
    {
        return $this->mName;
    }
    
    function setName($name)
    {
        $result = false;
    
        if ($this->mId and \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
        ->getDataAccess()
        ->Execute('UPDATE innowork_billing_vat_codes ' . 'SET vat=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->formatText($name) . ' ' . 'WHERE id=' . $this->mId)) {
            $this->mName = $name;
    
            $result = true;
        }
    
        return $result;
    }
    
    public function getDescription()
    {
        return $this->mDescription;
    }

    public function setDescription($description)
    {
        $result = false;
        
        if ($this->mId and \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->Execute('UPDATE innowork_billing_vat_codes ' . 'SET description=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->formatText($description) . ' ' . 'WHERE id=' . $this->mId)) {
            $this->mDescription = $description;
            
            $result = true;
        }
        
        return $result;
    }

    public function getPercentual()
    {
        return $this->mPercentual;
    }

    public function setPercentual($percentual)
    {
        $result = false;
        
        if ($this->mId and \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->Execute('UPDATE innowork_billing_vat_codes ' . 'SET percentual=' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->formatText($percentual) . ' ' . 'WHERE id=' . $this->mId)) {
            $this->mPercentual = $percentual;
            
            $result = true;
        }
        
        return $result;
    }

    public function remove()
    {
        $result = false;
        
        if ($this->mId and \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
            ->getDataAccess()
            ->Execute('DELETE FROM innowork_billing_vat_codes ' . 'WHERE id=' . $this->mId)) {
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()
                ->getDataAccess()
                ->Execute('UPDATE innowork_billing_invoices_rows ' . 'SET vatid=0 ' . 'WHERE vatid=' . $this->mId);
            
            $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
            
            if ($sets->GetDefaultVat() == $this->mId) {
                $sets->SetDefaultVat('0');
            }
            
            $this->mId = 0;
            $this->mDescription = $this->mPercentual = '';
            
            $result = true;
        }
        
        return $result;
    }
}


