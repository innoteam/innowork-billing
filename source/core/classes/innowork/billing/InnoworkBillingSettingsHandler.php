<?php

class InnoworkBillingSettingsHandler
{
	function GetDefaultVat()
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->GetKey( 'innoworkbilling-default-vat' );
	}

	function SetDefaultVat(
			$defaultVat
	)
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->SetKey( 'innoworkbilling-default-vat', $defaultVat );
	}

	function GetDefaultPayment()
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->GetKey( 'innoworkbilling-default-payment' );
	}

	function SetDefaultPayment(
			$defaultPayment
	)
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->SetKey( 'innoworkbilling-default-payment', $defaultPayment );
	}

	function GetInvoiceTemplate()
	{
		$result = '';

		$file_name = SITESTUFF_PATH.$GLOBALS['gEnv']['site']['id'].'/conf/innoworkbilling_invoice.html';

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
			require_once('locale/LocaleCatalog.php');
require_once('locale/LocaleCountry.php');

			$locale = new Locale(
					'innoworkbilling_misc',
					InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage()
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

		$file_name = SITESTUFF_PATH.$GLOBALS['gEnv']['site']['id'].'/conf/innoworkbilling_invoice.html';

		if ( $fp = fopen( $file_name, 'w' ) )
		{
			$result = fwrite( $fp, $invoiceTemplateContent );
		}

		return $result;
	}

	function GetNotifiesEmail()
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->GetKey( 'innoworkbilling-notifies-email' );
	}

	function SetNotifiesEmail(
			$email
	)
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->SetKey( 'innoworkbilling-notifies-email', $email );
	}

	function GetEmail()
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->GetKey( 'innoworkbilling-email' );
	}

	function SetEmail(
			$email
	)
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->SetKey( 'innoworkbilling-email', $email );
	}

	function GetSmtpServer()
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->GetKey( 'innoworkbilling-smtp-server' );
	}

	function SetSmtpServer(
			$server
	)
	{
		require_once('innomatic/domain/DomainSettings.php');
		$sets = new DomainSettings( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess() );
		return $sets->SetKey( 'innoworkbilling-smtp-server', $server );
	}
}