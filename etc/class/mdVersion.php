<?php
/**
 * Version information for the Mealodex
 * @author misterhaan
 */
class mdVersion {
	/**
	 * Database structure (tables and routines) version.  Changing this triggers
	 * the setup script in upgrade mode.
	 * @var integer
	 */
	const Structure = mdStructureVersion::Empty;
	/**
	 * Database data (rows) version.  Changing this triggers the setup script in
	 * update mode.
	 * @var integer
	 */
	const Data = mdDataVersion::Empty;
}

/**
 * List of structure versions for the Mealodex.  New versions should be added
 * at the top and use the next integer value.  Be sure to update
 * InstallDatabase() and UpgradeDatabaseStructure() in setup.php.
 * @author misterhaan
 */
class mdStructureVersion {
	const Empty = 0;
}

/**
 * List of data versions for the Mealodex.  New versions should be added at the
 * top and use the next integer value.  Be sure to update InstallDatabase() and
 * UpgradeDatabaseData() in setup.php.
 * @author misterhaan
 */
class mdDataVersion {
	const Empty = 0;
}
