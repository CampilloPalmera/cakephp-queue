<?php
namespace Queue\Test\TestCase\Controller\Admin;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 */
class QueueControllerTest extends IntegrationTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.queue.QueuedJobs',
		'plugin.queue.QueueProcesses'
	];

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->disableErrorHandlerMiddleware();
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex() {
		$this->_needsConnection();

		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testProcesses() {
		$this->get(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'processes']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testAddJob() {
		$jobsTable = TableRegistry::get('Queue.QueuedJobs');

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'addJob', 'Example']);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->orderDesc('id')->firstOrFail();
		$this->assertSame('Example', $job->job_type);
	}

	/**
	 * @return void
	 */
	public function testRemoveJob() {
		$jobsTable = TableRegistry::get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'removeJob', $job->id]);

		$this->assertResponseCode(302);

		$job = $jobsTable->find()->where(['id' => $job->id])->first();
		$this->assertNull($job);
	}

	/**
	 * @return void
	 */
	public function testResetJob() {
		$jobsTable = TableRegistry::get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $job->id]);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->find()->where(['id' => $job->id])->firstOrFail();
		$this->assertSame(0, $job->failed);
	}

	/**
	 * @return void
	 */
	public function testReset() {
		$jobsTable = TableRegistry::get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo',
			'failed' => 1,
		]);
		$jobsTable->saveOrFail($job);

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'reset']);

		$this->assertResponseCode(302);

		/** @var \Queue\Model\Entity\QueuedJob $job */
		$job = $jobsTable->get($job->id);
		$this->assertSame(0, $job->failed);
	}

	/**
	 * @return void
	 */
	public function testHardReset() {
		$jobsTable = TableRegistry::get('Queue.QueuedJobs');
		$job = $jobsTable->newEntity([
			'job_type' => 'foo'
		]);
		$jobsTable->saveOrFail($job);
		$count = $jobsTable->find()->count();
		$this->assertSame(1, $count);

		$this->post(['prefix' => 'admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'hardReset']);

		$this->assertResponseCode(302);

		$count = $jobsTable->find()->count();
		$this->assertSame(0, $count);
	}

	/**
	 * Helper method for skipping tests that need a real connection.
	 *
	 * @return void
	 */
	protected function _needsConnection() {
		$config = ConnectionManager::getConfig('test');
		$this->skipIf(strpos($config['driver'], 'Mysql') === false, 'Only Mysql is working yet for this.');
	}

}
