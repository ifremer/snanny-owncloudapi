<?php
/**
 * ownCloud - snannyowncloudapi
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Arnaud THOREL <athorel@asi.fr>
 * @copyright Arnaud THOREL 2015
 */

namespace OCA\SnannyOwncloudApi\Controller;

use PHPUnit_Framework_TestCase;

use OCP\AppFramework\Http\JsonResponse;


class ApiControllerTest extends PHPUnit_Framework_TestCase {

	private $controller;

	public function setUp() {
		$request = $this->getMockBuilder('OCP\IRequest')->getMock();

		$this->controller = new ApiController(
			'snannyowncloudapi', $request, null
		);
	}


	public function testIndex() {
		$result = $this->controller->index();
		$this->assertTrue($result instanceof JsonResponse);
	}
}