<?php

/**
 * SessionManager 
 *		Used to get user/session totals from YiiSession
 */
class SessionManager extends CComponent
{
	const ACTIVE_SESSION_MIN_TIME = '-1 hour';

	public function init()
	{
		// stub
	}
	/**
	 * getActiveUsers 
	 *		parse active sessions, looking for unique user logins for users not logged out
	 * @access public
	 * @return void
	 */
	public function getActiveUsers()
	{
		// get logged in users to compare against unique, active user sessions
		$users = User::model()->scopeMembersOnline()->findAll();
		$active_user_usernames = array_map(function($user) { return $user->username; },$users);
		// get active sessions
		$active_sessions = $this->getActiveSessions();
		// parse active sessions for unique users
		$active_user_sessions_usernames = array();
		foreach($active_sessions as $session)
		{
			if( ($session_username=$this->getSessionUsername($session)) !== null ) {
				// if username is unique among active user session usernames
				if( !in_array($session_username,$active_user_sessions_usernames) ) {
					// compare active user sessions to active logins
					if( in_array($session_username,$active_user_usernames) ) {
						array_push($active_user_sessions_usernames,$session_username);
					}
				}
			}
		}
		return $active_user_sessions_usernames;
	}
	/**
	 * getActiveGuests 
	 * 
	 * @access public
	 * @return void
	 */
	public function getActiveGuests()
	{
		// scope,init return object
		$return_sessions = array();
		// get active sessions
		$active_sessions = $this->getActiveSessions();
		foreach( $active_sessions as $active_session )
		{
			if( ($active_session_username=$this->getSessionUsername($active_session)) === null ) {
				array_push($return_sessions,$active_session);
			}
		}
		return $return_sessions;
	}
	/**
	 * getSessionUsername 
	 * 
	 * @param array $session_record 
	 *		The db record for the session
	 * @access protected
	 * @return mixed
	 *		The username if defined, null otherwise
	 */
	protected function getSessionUsername($session_record)
	{
		if( !empty($session_record['data']) )
		{
			// parse session data for username
			$data_components = explode(';',$session_record['data']);
			// return username if defined
			foreach( $data_components as $component )
			{
				if( !empty($component) )
				{
					list($key,$val) = explode('|',$component);
					// if this component is the username
					if( preg_match('/__name$/',$key) )
					{
						return preg_replace('/.*"(\w+)"$/','$1',$val);
					}
				}
			}
		}
		return null;
	}
	/**
	 * getActiveSessions 
	 * 
	 * @access protected
	 * @return void
	 */
	protected function getActiveSessions()
	{
		// create the min time for active session definition
		$active_session_min_time = date('Y-m-d H:i:s',strtotime(self::ACTIVE_SESSION_MIN_TIME));
		// retrieve active sessions
		$command = Yii::app()->db->createCommand('SELECT * FROM YiiSession WHERE lastActive > :lastActive')->bindParam(':lastActive',$active_session_min_time);
		// return active sessions
		return $command->queryAll();
	}
}
