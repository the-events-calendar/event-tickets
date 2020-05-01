<?php

// Should tell me return void is not available in PHP 5.6
class Foo {
	public function do_nothing(): void {

	}
}

// Should tell me anonymous class are not available in PHP 5.6
$a = new class {}

// Should tell me "create_function()" has been deprecated on PHP 7.2
create_function('', '');