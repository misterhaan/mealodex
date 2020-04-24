<?php
require_once dirname(__DIR__) . '/etc/class/api.php';

/**
 * Handler for unit API requests.
 * @author misterhaan
 */
class UnitApi extends Api {
		/**
	 * List all units the Mealodex knows about.
	 */
	protected static function GET_list() {
		if($db = self::RequireLatestDatabase())
			if($list = $db->prepare('select id, measure, abbr, name, factor from unit order by measure, factor'))
				if($list->execute()) {
					$unit = new stdClass();
					if($list->bind_result($unit->id, $unit->measure, $unit->abbr, $unit->name, $unit->factor)) {
						$units = [];
						while($list->fetch()) {

							$units[] = self::CloneObject($unit);
						}
						self::Success($units);
					} else
						self::DatabaseError('Error binding unit list result', $list);
				} else
					self::DatabaseError('Error listing units', $list);
			else
				self::DatabaseError('Error preparing to list units', $db);
	}
}
UnitApi::Respond();
