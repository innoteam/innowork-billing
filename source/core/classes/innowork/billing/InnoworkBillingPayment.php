<?php

class InnoworkBillingPayment
{
	var $mId = 0;
	var $mDescription;
	var $mDays = 0;
	var $mMonthEnd = false;

	function InnoworkBillingPayment(
			$id = 0
	)
	{
		$id = (int)$id;

		if ( $id )
		{
			$check_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
					'SELECT * '.
					'FROM innowork_billing_payments '.
					'WHERE id='.$id
			);

			if ( $check_query->getNumberRows() )
			{
				$this->mId = $id;
				$this->mDescription = $check_query->getFields( 'description' );
				$this->mDays = $check_query->getFields( 'days' );
				$this->mMonthEnd = $check_query->getFields( 'monthend' ) == InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue ? true : false;

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
			$id = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getNextSequenceValue( 'innowork_billing_payments_id_seq' );
			$days = (int)$days;
			if ( !strlen( $days ) ) $days = 0;

			if ( InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
					'INSERT INTO innowork_billing_payments VALUES ('.
					$id.','.
					InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $description ).','.
					$days.','.
					InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(
							$monthEnd ?
							InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue :
							InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse
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
		InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
				'UPDATE innowork_billing_payments '.
				'SET description='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText( $description ).' '.
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
		InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
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
		InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
				'UPDATE innowork_billing_payments '.
				'SET monthend='.(
						$monthEnd ?
						InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmttrue :
						InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->fmtfalse
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

	function remove()
	{
		$result = false;

		if (
		$this->mId
		and
		InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
				'DELETE FROM innowork_billing_payments '.
				'WHERE id='.$this->mId
		)
		)
		{
			InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
					'UPDATE innowork_billing_invoices_rows '.
					'SET paymentid=0 '.
					'WHERE paymentid='.$this->mId
			);

			require_once('innowork/billing/InnoworkBillingSettingsHandler.php');
			$sets = new InnoworkBillingSettingsHandler();

			if ( $sets->getDefaultPayment() == $this->mId )
			{
				$sets->setDefaultPayment( '0' );
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