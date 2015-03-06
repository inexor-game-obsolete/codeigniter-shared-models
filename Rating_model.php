<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Rating_model extends MY_Model
{
	/**
	 * Magic Method __construct();
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load_config('rating');
	}

	/**
	 * Gets the number of votings for a module-identifier
	 * @param  boolean $positives  if positives or negatives should be counted
	 * @param  string  $module     the module to count from
	 * @param  string  $identifier the identifier to count from
	 * @return int                 the votings, ALWAYS A POSITIVE NUMBER
	 */
	public function get($positives, $module, $identifier)
	{
		$where = array(
			'module'     => $module,
			'identifier' => $identifier,
			'rating'     => $positives ? 1 : 0
		);
		return $this->db->get_where($this->Table, $where)->num_rows();
	}
	public function get_positive($module, $identifier)
	{
		return $this->get(true, $module, $identifier);
	}
	public function get_negative($module, $identifier)
	{
		return $this->get(false, $module, $identifier);
	}

	/**
	 * Counts the votings
	 * @param string $module     the module to count from
	 * @param string $itentifier the identifier to count from
	 * @return int               the number of votes
	 */
	public function count($module, $identifier)
	{
		return $this->get_where($this->Table, array('module' => $module, 'identifier' => $identifier))->num_rows();
	}

	/**
	 * Checks if a user has rated.
	 * @param  int    $userid     id of the user to check
	 * @param  string $module     module-name
	 * @param  string $identifier identifier-name
	 * @return int                if not rated 0; positive: 1; negative: -1;
	 */
	public function user_rating($userid, $module, $identifier)
	{
		$q = $this->db->get_where($this->Table, array('module' => $module, 'identifier' => $identifier, 'user_id' => $userid));
		if($q->num_rows() == 0)
			return 0;
		if($q->row()->rating == 1)
			return 1;
		return -1;
	}

	/**
	 * Rates. 
	 * @param  int    $userid     user-id
	 * @param  int    $rating     -1 for negative; 0 for none (deletes ratings); 1 for positive
	 * @param  string $module     the module-name
	 * @param  string $identifier the identifier-name
	 */
	public function rate($userid, $rating, $module, $identifier)
	{
		$rated = $this->user_rating($userid, $module, $identifier);
		if($rated == $rating)
			return;

		$where = array(
			'user_id'    => $userid, 
			'module'     => $module, 
			'identifier' => $identifier
		);

		if($rating == 0)
		{
			$this->db->delete($this->Table, $where);
			return;
		}

		if($rated !== 0)
		{
			$this->db->where($where)->update($this->Table, array('rating' => $rating == -1 ? 0 : 1));
			return;
		}

		$this->db->insert($this->Table, array(
			'user_id'    => $userid,
			'rating'     => $rating == -1 ? 0 : 1,
			'module'     => $module,
			'identifier' => $identifier,
			'timestamp'  => date('Y-m-d H:i:s')
		));
		return;
	}

}