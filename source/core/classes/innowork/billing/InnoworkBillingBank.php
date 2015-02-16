<?php
namespace Innowork\Billing;

class InnoworkBillingBank
{
    protected $dataAccess;
    public $id = 0;
    public $name;
    public $description;

    public function __construct($id = 0)
    {
        $id = (int)$id;
        $this->dataAccess = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        if ($id) {
            $check_query = $this->dataAccess 
                ->execute('SELECT * ' . 'FROM innowork_billing_banks ' . 'WHERE id=' . $id);
            
            if ($check_query->getNumberRows()) {
                $this->id = $id;
                $this->name = $check_query->getFields('bank');
                $this->description = $check_query->getFields('description');
                
                $check_query->free();
            }
        }
    }

    public function create($name, $description)
    {
        $result = false;
        
        if (! $this->id) {
            $domain_da = $this->dataAccess; 
            
            $id = $domain_da->getNextSequenceValue('innowork_billing_banks_id_seq');
            
            if ($domain_da->execute('INSERT INTO innowork_billing_banks VALUES (' . $id . ',' . 
                $domain_da->formatText($name) . ','.
                $domain_da->formatText($description) . ')')) {
                $this->id = $id;
                $this->name = $name;
                $this->description = $description;
                
                $result = true;
            }
        }
        
        return $result;
    }

    public function getName()
    {
        return $this->name;
    }
    
    function setName($name)
    {
        if ($this->id and $this->dataAccess 
            ->execute('UPDATE innowork_billing_banks ' . 'SET bank=' . 
            $this->dataAccess->formatText($name) . ' ' . 'WHERE id=' . $this->id)) {
            $this->name = $name;
        }
    
        return $this;
    }
    
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        if ($this->id and 
            $this->dataAccess->execute('UPDATE innowork_billing_banks ' . 'SET description=' . 
            $this->dataAccess->formatText($description) . ' ' . 'WHERE id=' . $this->id)) {
            $this->description = $description;
        }
        
        return $this;
    }

    public function remove()
    {
        $result = false;
        
        if ($this->id and 
            $this->dataAccess->execute('DELETE FROM innowork_billing_banks ' . 'WHERE id=' . $this->id)) {
                $this->dataAccess->execute('UPDATE innowork_billing_invoices ' . 'SET bankid=0 ' . 'WHERE bankid=' . $this->id);
            
            $sets = new \Innowork\Billing\InnoworkBillingSettingsHandler();
            
            if ($sets->getDefaultBank() == $this->id) {
                $sets->setDefaultBank('0');
            }
            
            $this->id = 0;
            $this->description = $this->name = '';
            
            $result = true;
        }
        
        return $result;
    }
    
    /**
     * Extracts the list of available banks.
     * 
     * @return array
     */
    public static function getBankList()
    {
        $query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute(
            'SELECT id,bank FROM innowork_billing_banks ORDER BY bank'
        );
        
        $list = array();
        while (!$query->eof) {
            $list[$query->getFields('id')] = $query->getFields('bank');
            $query->moveNext();
        }
        
        return $list;
    }
}


