<?php

/**
 * Version information for the Mealodex
 * @author misterhaan
 */
class Version {
	/**
	 * Database structure (tables and routines) version.  Changing this triggers
	 * the setup script in upgrade mode.
	 * @var int
	 */
	const Structure = StructureVersion::Recipes;
	/**
	 * Database data (rows) version.  Changing this triggers the setup script in
	 * update mode.
	 * @var int
	 */
	const Data = DataVersion::Recipes;
}

/**
 * List of structure versions for the Mealodex.  New versions should be added
 * at the top and use the next integer value.  Be sure to update
 * InstallDatabase() and UpgradeDatabaseStructure() in setup.php.
 * @author misterhaan
 */
class StructureVersion {
	const Recipes = 1;
	const Empty = 0;
}

/**
 * List of data versions for the Mealodex.  New versions should be added at the
 * top and use the next integer value.  Be sure to update InstallDatabase() and
 * UpgradeDatabaseData() in setup.php.
 * @author misterhaan
 */
class DataVersion {
	const Recipes = 1;
	const Empty = 0;
}
