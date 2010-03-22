<?php

require 'gepi/om/BaseJTraitementSaisieEleve.php';


/**
 * Skeleton subclass for representing a row from the 'j_traitements_saisies' table.
 *
 * Table de jointure entre la saisie et le traitement des absences
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    gepi
 */
class JTraitementSaisieEleve extends BaseJTraitementSaisieEleve {

	/**
	 * Initializes internal state of JTraitementSaisieEleve object.
	 * @see        parent::__construct()
	 */
	public function __construct()
	{
		// Make sure that parent constructor is always invoked, since that
		// is where any default values for this object are set.
		parent::__construct();
	}

} // JTraitementSaisieEleve