<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Comments_model extends MY_Model
{
	/**
	 * Magic Method __construct();
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load_config('comments');
	}

	/**
	 * Gets a comment by id.
	 * 
	 * @param  int $id id of the comment
	 * @return object     object of the comment
	 */ 
	public function get($id)
	{
		$query = $this->db->get_where($this->Table, array('id' => $id));
		return $query->row();
	}
	public function get_by_id($id)
	{
		return $this->get($id);
	}

	/**
	 * Checks how many direcot answers to a comment exist.
	 * 
	 * @param  int $id id of the comment
	 * @return int     number of direct comments
	 */
	public function answers_to($id)
	{
		return $this->db->where('answer_to', $id)->get($this->Table)->num_rows();
	}

	/**
	 * Returns the number of all comments which are not answers.
	 * @return int number of comments
	 */
	public function count()
	{
		return $this->db->where('answer_to', NULL)->get($this->Table)->num_rows();
	}

	/**
	 * Gets the path (as an array) to the answer-id
	 * @param  int   $id comment-id
	 * @return array     contains the path. first is the main-comment, last is the id itself, second-last is the parent-comment-id
	 */
	public function path_to($id)
	{
		if(!isint($id))
			return array();

		$return = array($id);
		$break = false;
		while(!$break)
		{
			$answer = $this->db->select('answer_to')->where('id', $return[0])->get($this->Table)->row()->answer_to;
			if(isint($answer))
				array_unshift($return, $answer);
			else
			{
				break;
				$break = true;
			}
		}
		return $return;
	}

	/**
	 * Gets all comments and answers.
	 * for each depth limit needs one value. its the limit for answers to be loaded.
	 *
	 * @param  string $module module of the comment section
	 * @param  string $identifier identifier of the comment section
	 * @param  string $order order of the loading date
	 * @param  int $limit limit for comments
	 * @param  int $offset offset to the limit
	 * @return object comments
	 */
	public function get_comments($module, $identifier, $order = "ASC", $limit = 30, $offset = 0)
	{
		$this->db->order_by('date', $order);
		$this->db->limit($limit, $offset);
		$query = $this->db->get_where(
			$this->Table,
			array(
				"module"     => $module,
				"identifier" => $identifier
			)
		);

		return $query->result();
	}

	/**
	 * Returns comments which are positioned before/after $id
	 * @param  int     $id     reference-position
	 * @param  boolean $after  should it be after or before reference
	 * @param  string  $order  SQL-order
	 * @param  integer $limit  SQL-limit
	 * @param  integer $offset SQL-offset
	 * @return array           containing objects of comments.
	 */
	public function get_comments_positioned_to($id, $after = false, $order = "DESC", $limit = 30, $offset = 0)
	{

		$rel = $this->get($id);
		$where = array(
			'id ' . ($after ? '>' : '<') => $id,
			'module'                     => $rel->module,
			'identifier'                 => $rel->identifier,
			'answer_to'                  => $rel->answer_to
		);
		return $this->db->where($where)->order_by('date', $order)->limit($limit, $offset)->get($this->Table)->result();
	}

	/**
	 * Shortcut for $this->get_comments_positioned_to($id, true, $order, $limit, $offset);
	 */
	public function get_comments_after($id, $order = "DESC", $limit = 30, $offset = 0)
	{
		return $this->get_comments_positioned_to($id, true, $order, $limit, $offset);
	}

	/**
	 * Shortcut for $this->get_comments_positioned_to($id, false, $order, $limit, $offset);
	 */
	public function get_comments_before($id, $order = "DESC", $limit = 30, $offset = 0)
	{
		return $this->get_comments_positioned_to($id, false, $order, $limit, $offset);
	}


	/**
	 * Returns how many comments are positioned before/after the reference-comment
	 * @param  int     $id    id of the reference-comment
	 * @param  boolean $after whether the comments after of before reference should be counted
	 * @return int            the number of comments befor/after
	 */
	public function count_comments_positioned_to($id, $after = false)
	{
		$rel = $this->get($id);
		$where = array(
			'id ' . ($after ? '>' : '<') => $id,
			'module'                     => $rel->module,
			'identifier'                 => $rel->identifier,
			'answer_to'                  => $rel->answer_to
		);
		return $this->db->where($where)->get($this->Table)->num_rows();
	}

	/**
	 * Shortcut for $this->count_comments_positioned_to($id, true);
	 */
	public function count_comments_after($id)
	{
		return $this->count_comments_positioned_to($id, true);
	}

	/**
	 * Shortcut for $this->count_comments_positioned_to($id, false);
	 */
	public function count_comments_before($id)
	{
		return $this->count_comments_positioned_to($id, false);
	}

	/**
	 * Gets answers to a comment
	 * 
	 * @param  int     $id     the comment id
	 * @param  string  $order  order of the loading (by date)
	 * @param  integer $limit  limit for comments
	 * @param  integer $offset offset to the limit
	 * @return object          answers
	 */
	public function get_answers($id, $order = "DESC", $limit = 10, $offset = 0)
	{
		$this->db->order_by('date', $order);
		$this->db->limit($limit, $offset);
		$result = $this->db->get_where($this->Table, array("answer_to" => $id))->result();
		return $result;
	}

	/**
	 * Creates a new comment to a comment section
	 * 
	 * @param  string $module     the comment module
	 * @param  string $identifier the identifier for the module-section
	 * @param  int    $userid     id of the user who submits the comment
	 * @param  string $comment    the comment itself
	 * @return int                id of the answer
	 */
	public function comment($module, $identifier, $userid, $comment)
	{
		$this->db->insert($this->Table, array(
			'module'     => $module,
			'identifier' => $identifier,
			'user_id'    => $userid,
			'comment'    => $comment,
			'date'       => date('Y-m-d H:i:s')
		));
		return $this->db->insert_id();
	}

	/**
	 * Checks if a comment exists
	 * 
	 * @param  int     $id the comment id
	 * @return boolean     true if exists
	 */
	public function comment_exists($id)
	{
		return ($this->db->get_where($this->Table, array('id' => $id))->num_rows() == 1) ? true : false;
	}

	/**
	 * Answer to a comment
	 * Inserts row in the db.
	 *
	 * @param int $id         id of the comment to answer to
	 * @param int $userid     id of the user who submits the comment
	 * @param string $comment the comment content
	 * @return int            id of the answer
	 */
	public function answer($id, $userid, $comment)
	{
		$this->db->insert($this->Table, array(
			'answer_to' => $id,
			'user_id'   => $userid,
			'comment'   => $comment,
			'date'      => date('Y-m-d H:i:s')
		));
		return $this->db->insert_id();
	}
}