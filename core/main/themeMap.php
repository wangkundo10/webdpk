<?php
include_once("theme.php");

/**
 * Maps different themes to specific directories.
 *
 * TODO: Get rid of this class.
 *
 * @author Scott Bailey (scott@zeddic.com)
 */
class themeMap {
	var $id;
	var $path;
	var $theme;
	const tablename = "site_theme_map";

	function themeMap() {
		$this->tablename = themeMap::tablename;
	}

	/**
	 * Loads the information for this class from the backend database
	 * using the passed string.
	 */
	function loadFromDatabase($id) {
		global $sql;
		$id = sql::Escape($id);
		$row = $sql->QueryRow("SELECT * FROM $this->tablename WHERE id='$id'");
		$this->loadFromRow($row);
	}

	/**
	 * Loads the information for this class from the backend database
	 * using the passed string.
	 **/
	function loadFromDatabaseFromPath($path) {
		global $sql;
		$path = sql::Escape($path);
		$row = $sql->QueryRow("SELECT * FROM $this->tablename WHERE path='$path'");
		$this->loadFromRow($row);
	}

	/**
	 * Loads the information for this class from the passed database row.
	 */
	function loadFromRow($row) {
		$this->id=$row["id"];
		$this->path = $row["path"];
		$this->theme = $row["theme"];
	}

	/**
	 * Saves data into the backend database using the supplied id
	 */
	function save() {
		global $sql;
		$path = sql::Escape($this->path);
		$sql->Query("UPDATE $this->tablename SET
					path = '$path',
					theme = '$this->theme'
					WHERE id='$this->id'");
	}

	/**
	 * Saves data into the backend database as a new row entry. After
	 * calling this method $id will be filled with a new value
	 * matching the new row for the data
	 */
	function saveNew() {
		global $sql;
		$path = sql::Escape($this->path);
		$sql->Query("INSERT INTO $this->tablename SET
					path = '$path',
					theme = '$this->theme'
					");
		$this->id=$sql->GetLastId();
	}

	/**
	 * Deletes the row with the current id of this instance from the
	 * database
	 */
	function delete() {
		global $sql;
		$sql->Query("DELETE FROM $this->tablename WHERE id = '$this->id'");
	}
	/**
	 * Returns true if the given entry exists in the database
	 * database
	 */
	function exists($path) {
		global $sql;
		$path = sql::escape($path);
		$tablename = themeMap::tablename;
		$exists = $sql->QueryItem("SELECT id FROM $tablename WHERE path='$path'"); //MODIFY THIS LINE
		return ($exists != "");
	}

	/**
	 * Given a path this will return a theme instance that represents it
	 * occording to a database entry. Note that this does NOT search
	 * the database for any available match, but merly looks for an
	 * exact match between the given path and an entry. This method is
	 * intended for PRIVATE / INTERNAL use within the class only.
	 * To determine a theme for a given path, searching the database,
	 * please use getThemeForPath()
	 */
	function loadTheme($path){
		//first, check the map to see to see what theme id
		//the path is mapped to
		unset($themeMap);
		$themeMap = new themeMap();
		$themeMap->loadFromDatabaseFromPath($path);
		//there was not match in the map...
		if($themeMap->id == "")
			return null;

		unset($theme);
		$theme = new theme();
		$theme->loadFromDatabase($themeMap->theme);
		if($theme->id == "")
			return null;

		return $theme;
	}

	/**
	 * Given a path, will return a theme that is appropriate for
	 * this path. If no theme is specified for the given path
	 * in the theme / path map, null is returned.
	 * Note that this method will search multiple cases to find an
	 * appropriate theme, trimming the path until it finds a match or it
	 * eliminates all possible matches.
	 * For example, if the path was "ControlPanel/Users/User" was passed
	 * and there was an entry in the theme map for "ControlPanel", this method
	 * would recursivly test:
	 * "ControlPanel/Users/User",
	 * "ControlPanel/Users"
	 * "ControlPanel" (Match)
	 */
	function getThemeForPath($path){
		//make sure the path is trimmed in an appropriate form to begin
		//with
		$ext = fileutil::getExt($path);
		if($ext != "")
			$path = fileutil::stripFile($path);
		if ($path == "" || $path[0] != "/") {
			$path = "/" . $path;
		}
		//call the recursive method
		$theme =  themeMap::checkPathRecursive($path);
		return $theme;
	}

	/**
	 * Reursivly checks a path until it finds a theme. For internal use
	 * only.
	 */
	function checkPathRecursive($path){
		//echo("CHECK: $path <br />");
		if (themeMap::exists($path)) {
			//echo("MATCH: $path <br />");
			return themeMap::loadTheme($path);
		}
		if($path == "" || $path == "/") {
			//echo("NO MATCH<br />");
			return null;
		}

		$path = fileutil::upPath($path);
		if ($path == "" || $path[0] != "/") {
			$path = "/" . $path;
		}

		return themeMap::checkPathRecursive($path);
	}

	/**
	 * Checks to see if the classes database table exists. If it does not
	 * the table is created.
	 */
	function setupTable() {
		if(!sql::TableExists(themeMap::tablename)) {
			$tablename = themeMap::tablename;
			global $sql;
			$sql->Query("CREATE TABLE `$tablename` (
						`id` INT NOT NULL AUTO_INCREMENT ,
						`path` VARCHAR (256) NOT NULL,
						`theme` INT NOT NULL,
						PRIMARY KEY ( `id` )
						) ENGINE = innodb;");
		}
	}
}
themeMap::setupTable()
?>
