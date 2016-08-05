<?php
namespace Terminus\Commands;
use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Collections\Sites;
use Terminus\Models\Site;
use Terminus\Models\User;
use Terminus\Models\Workflow;
use Terminus\Session;
/**
 * Remove being a team member to all sites that you do not own.
 * 
 * @command sites
 */
class SitesDelmeCommand extends TerminusCommand {
	public $sites;
	/**
	 * Report the status of all available sites that you are a team member
	 *
	 * @param array $options Options to construct the command object
	 * @return SitesStatusCommand
	 */
	public function __construct(array $options = []) {
		$options['require_login'] = true;
		parent::__construct($options);

		$this->log()->info("[+] Start fetching sites\r\n");

		//Load all sites
		$this->sites = new Sites();

		//Get users info
		$user = Session::getUser();
		$user->fetch();
		$this->user = $user->serialize();
	}
	/**
	 * Report the status of all available sites that you are a team member
	 * Note: because of the size of this call, it is cached
	 *   and also is the basis for loading individual sites by name
	 *
	 * [--cached]
	 * : Causes the command to return cached sites list instead of retrieving anew
	 *
	 * @subcommand delme
	 * @alias dm
	 */
	public function delme($args, $assoc_args) {

		// --cached will skip rebuilding of cache
		if (!isset($assoc_args['cached'])) {
			$this->sites->rebuildCache();
		}
		
		$sites = $this->sites->all();

		// Get sites that you are a team member
		$sites = $this->filterByTeamMembership($sites);
		
		$owner_uuid = Session::getData()->user_uuid;
		
		// Get sites that you don't own
		$sites = $this->filterByOwner($sites, $owner_uuid);

		$sites = $this->filterByExcludeList($sites);

		if (count($sites) == 0) {
			$this->log()->warning('You have no sites.');
		}

		$site_rows = array();
		$site_labels = [
			'name'            => 'Name',
			'service_level'   => 'Service Level',
			'framework'       => 'Framework',
			'membership'	  => 'Membership',
			'owner_id'		  => 'Owner'
		];

		// Loop through each site and collect status data.
		foreach ($sites as $site) {
			$name = $site->get('name');
			$frozen = 'no';
			if ($site->get('frozen')) {
				$frozen = 'yes';
			}
			$site_rows[] = [
			'name'            => $name,
			'service_level'   => $site->get('service_level'),
			'framework'       => $site->get('framework'),
			'membership'	  => $site->get('membership')['id'],
			'owner_id'		  => $owner_uuid,
			];
		}
		
		// Output the status data in table format.		
		$this->output()->outputRecordList($site_rows, $site_labels);
		
		// Loop through each site and remove membership
		foreach ($sites as $site) {
			$name = $site->get('name');			
			$this_site = $this->sites->get($name);
			$this_team = $this_site->user_memberships;
			$this_user = $this_team->get($this->user['email']);
			if ($this_user != null) {
	          $workflow = $this_user->removeMember($this->user['email']);
	          $this->workflowOutput($workflow);
	        } else {
	          $this->failure(
	            '"{member}" is not a valid member.',
	            array('member' => $this->user['email'])
	          );
	        }
		
		}
		
		$this->log()->info("[+] End\r\n");
	}
	/**
	 * Filters an array of sites by whether the user is an organizational member
	 *
	 * @param Site[] $sites An array of sites to filter by
	 * @param string $owner_uuid UUID of NOT the owning user to filter by
	 * @return Site[]
	 */
	private function filterByOwner($sites, $owner_uuid) {
		$filtered_sites = array_filter(
			$sites,
			function($site) use ($owner_uuid) {
				$is_owner = ($site->get('owner') != $owner_uuid);
				return $is_owner;
			}
		);
		return $filtered_sites;
	}
	/**
	 * Filters an array of sites by whether the user is a team member
	 *
	 * @param Site[] $sites An array of sites to filter by
	 * @return Site[]
	 */
	private function filterByTeamMembership($sites) {
		$filtered_sites = array_filter(
			$sites,
			function($site) {
				$memberships = $site->get('memberships');
				foreach ($memberships as $membership) {
					if ($membership['name'] == 'Team') {
						return true;
					}
				}
				return false;
			}
		);
		return $filtered_sites;
	}
	/**
	 * Filters an array of sites by exclude list and --exclude filter
	 *
	 * @param Site[] $sites An array of sites to filter by
	 * @return Site[]
	 */
	private function filterByExcludeList($sites, $exclude_list = array()) {
		
		$filtered_sites = array_filter(
			$sites,
			function($site) {
				$exclude_merge_list =  $this->exclude_site_list(); 
				if (!in_array($site->get('name'), $exclude_merge_list)) {
					return true;
				}
				return false;
			}
		);
		return $filtered_sites;
	}
	/*
	* Exclude List 
	*/
	private function exclude_site_list($exclude_list=array()) {
		$predefined_exclude_list = array('pantheon-assets');
		$exclude_merge_list = array_merge($predefined_exclude_list, $exclude_list);
		return $exclude_merge_list;
	}
}
