<?php

class InnoworkBillingVat
{
	var $mId = 0;
	var $mDescription;
	var $mPercentual;

	function InnoworkBillingVat(
			$id = 0
	)
	{
		$id = (int)$id;

		if ( $id )
		{
			$check_query = &InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
					'SELECT * '.
					'FROM innowork_billing_vat_codes '.
					'WHERE id='.$id
			);

			if ( $check_query->getNumberRows() )
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
			$id = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->NextSeqValue( 'innowork_billing_vat_codes_id_seq' );

			if ( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
					'INSERT INTO innowork_billing_vat_codes VALUES ('.
					$id.','.
					InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Format_Text( $description ).','.
					InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Format_Text( $percentual ).')' ) )
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
		InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
				'UPDATE innowork_billing_vat_codes '.
				'SET vat='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Format_Text( $description ).' '.
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
		InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
				'UPDATE innowork_billing_vat_codes '.
				'SET percentual='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Format_Text( $percentual ).' '.
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
		InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
				'DELETE FROM innowork_billing_vat_codes '.
				'WHERE id='.$this->mId
		)
		)
		{
			InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
					'UPDATE innowork_billing_invoices_rows '.
					'SET vatid=0 '.
					'WHERE vatid='.$this->mId
			);

			$sets = new InnoworkBillingSettingsHandler();

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


