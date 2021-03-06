<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Pgroups_permissions_model extends MY_Model {
	/**
	 * Magic Method __construct();
	 */
	public function __construct() {
		parent::__construct();
		$this->load_config('pgroups_permissions');
	}

	/**
	 * Gets all permission-id by group-id
	 * @param int $id group-id
	 * @return array containing objects
	 */
	public function get_permissions_by_group($id) {
		$query = $this->db->get_where($this->Table, array('pgroup_id' => $id));
		return $query->result_array();
	}

	/**
	 * Gets all groups which have set this permission
	 * @param int $id permission-id
	 * @return array containing objects
	 */
	public function get_permission($id) {
		$query = $this->db->get_where($this->Table, array('permissions_id' => $id));
		return $query->result_array();
	}

	/**
	 * Checks if a group has a permission
	 * @param int $groupid group-id
	 * @param int $permissionid permission-id
	 * @return mixed INT(0) if is not set in the table; BOOL if is set (TRUE if group has the permission; else FALSE)
	 */
	public function has_group_permission($groupid, $permissionid) {
		$query = $this->db->get_where($this->Table, array('pgroup_id' => $groupid, 'permissions_id' => $permissionid));
		if($query->num_rows() === 1)
		{
			$data = $query->row();
			if($data->value == TRUE)
			{
				return true;
			} else {
				return false;
			}
		} else {
			return 0;
		}
	}

	/**
	 * Updates an entry value
	 * @param int $gid group-id
	 * @param int $pid permission-id
	 * @param bool $value The new value
	 * @return The Active-record query-return
	 */
	public function update($gid, $pid, $value) {
		$this->db->where('pgroup_id', $gid);
		$this->db->where('permissions_id', $pid);
		return $this->db->update($this->Table, array('value' => $value));
	}

	/**
	 * Inserts a new entry
	 * @param int $gid group-id
	 * @param int $pid permission-id
	 * @param bool $value The value (Whether a group has the permission)
	 * @return The Active-record query-return
	 */
	public function insert($gid, $pid, $value) {
		return $this->db->insert($this->Table, array(
			'pgroup_id' => $gid,
			'permissions_id' => $pid,
			'value' => $value
		));
	}

	/**
	 * Checks if a permission is specifically set for a group
	 * @param int $gid group-id
	 * @param int $pid permission-id
	 * @return bool
	 */
	public function exists($gid, $pid) {
		$this->db->where('pgroup_id', $gid);
		$this->db->where('permissions_id', $pid);
		$this->db->limit(1, 0);
		if($this->db->get($this->Table)->num_rows() > 0) return true;
		return false;
	}

	/**
	 * Deletes an entry based on group and permission
	 * @param int $gid group-id
	 * @param int $pid permission-id
	 */
	public function delete($gid, $pid) {
		$this->db->where('pgroup_id', $gid);
		$this->db->where('permissions_id', $pid);
		$this->db->delete($this->Table);
	}

	/**
	 * Checks if a group has multiple permissions
	 * @param int $group group-id
	 * @param array $permissions the needed permissions
	 * @param array &$permissions_array the results for the permissions
	 * @return mixed 
	 * -> ARRAY(permissions) permissions which are still needed and not set in the DB
	 * -> BOOL
	 */
	public function group_has_permissions($group, $permissions, &$permissions_array = array()) {
		return $this->groupset_have_permissions(array($group), $permissions, $permissions_array);
	}

	public function groupset_have_permissions($groupset, $permissions, &$permissions_array = array()) {
		if(!is_array($permissions_array)) $permissions_array = array();
		foreach($groupset as $g) {
			$needed = array();
			// Check if permission was already read from db
			foreach($permissions as $p) {
				if(isset($permissions_array[$g][$p]) && $permissions_array[$g][$p] == false) return false;
				if(!isset($permissions_array[$g][$p])) $needed[] = $p;
				elseif($permissions_array[$g][$p] == true) unset($permissions[$p]);
			}

			if(count($needed) !== 0) {
				$this->db->where('pgroup_id', $g);
				$this->db->where_in('permissions_id', $needed);
				$result = $this->db->get($this->Table)->result_array();
				$res = true;
				foreach($result as $r) {
					if($r['value'] == false) $res = false;
					unset($permissions[array_search($r['permissions_id'], $permissions)]);
					$permissions_array[$g][$r['permissions_id']] = (int) $r['value'];
				}
			}
			foreach($permissions as $p) {
				$permissions_array[$g][$p] = NULL;
			}
			if(isset($res) && $res == false) return false;
			elseif(count($permissions) == 0) return true;
		}
		return $permissions;
	}

	/**
	 * Sets a permission for a group
	 * @param int $groupid group-id
	 * @param int $permissionid permission-id
	 * @param bool $value The value whether the group has the permission
	 */
	public function set_group_permission($groupid, $permissionid, $value) {
		if($value == true) $value = true;
		else $value = false;
		$current_permission = $this->has_group_permission($groupid, $permissionid);
		if($current_permission===0)
		{
			$this->db->insert($this->Table, array(
				'group_id' => $groupid,
				'permission_id' => $permissionid,
				'value' => $value
			));
		} elseif($current_permission != $value) {
			$this->db->where('group_id', $groupid);
			$this->db->where('permission_id', $permissionid);
			$this->db->update($this->Table, array('value' => $value));
		}
	}

}