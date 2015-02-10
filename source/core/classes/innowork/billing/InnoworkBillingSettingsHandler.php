<?php
namespace Innowork\Billing;

class InnoworkBillingSettingsHandler
{

    public static function getDefaultVat()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-default-vat');
    }

    public static function getDefaultBank()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-default-bank');
    }

    public static function setDefaultVat($defaultVat)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-default-vat', $defaultVat);
    }

    public static function setDefaultBank($defaultBank)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-default-bank', $defaultBank);
    }

    public static function getDefaultPayment()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-default-payment');
    }

    public static function setDefaultPayment($defaultPayment)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-default-payment', $defaultPayment);
    }

    public static function getInvoiceTemplate()
    {
        $result = '';
        
        $file_name = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/domains/' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId() . '/conf/innoworkbilling_invoice.html';
        
        if (file_exists($file_name) and $fp = fopen($file_name, 'r')) {
            $result = fread($fp, filesize($file_name));
            fclose($fp);
        } else {
            $locale = new \Innomatic\Locale\LocaleCatalog('innowork-billing::misc', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());
            
            $result = $locale->GetStr('notemplate_set');
        }
        
        return $result;
    }

    public static function setInvoiceTemplate($invoiceTemplateContent)
    {
        $result = false;
        
        $file_name = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/domains/' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId() . '/conf/innoworkbilling_invoice.html';
        
        if ($fp = fopen($file_name, 'w')) {
            $result = fwrite($fp, $invoiceTemplateContent);
        }
        
        return $result;
    }

    public static function getNotifiesEmail()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-notifies-email');
    }

    public static function setNotifiesEmail($email)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-notifies-email', $email);
    }

    public static function getEmail()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-email');
    }

    public static function setEmail($email)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-email', $email);
    }

    public static function getSmtpServer()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-smtp-server');
    }

    public static function setSmtpServer($server)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-smtp-server', $server);
    }
}