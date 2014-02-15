<?php
namespace Innowork\Billing;

class InnoworkBillingSettingsHandler
{

    public static function GetDefaultVat()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-default-vat');
    }

    public static function SetDefaultVat($defaultVat)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-default-vat', $defaultVat);
    }

    public static function GetDefaultPayment()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-default-payment');
    }

    public static function setDefaultPayment($defaultPayment)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-default-payment', $defaultPayment);
    }

    public static function GetInvoiceTemplate()
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

    public static function SetInvoiceTemplate($invoiceTemplateContent)
    {
        $result = false;
        
        $file_name = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() . 'core/domains/' . \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId() . '/conf/innoworkbilling_invoice.html';
        
        if ($fp = fopen($file_name, 'w')) {
            $result = fwrite($fp, $invoiceTemplateContent);
        }
        
        return $result;
    }

    public static function GetNotifiesEmail()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-notifies-email');
    }

    public static function SetNotifiesEmail($email)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-notifies-email', $email);
    }

    public static function GetEmail()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-email');
    }

    public static function SetEmail($email)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-email', $email);
    }

    public static function GetSmtpServer()
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->GetKey('innoworkbilling-smtp-server');
    }

    public static function SetSmtpServer($server)
    {
        $sets = new \Innomatic\Domain\DomainSettings(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        return $sets->SetKey('innoworkbilling-smtp-server', $server);
    }
}