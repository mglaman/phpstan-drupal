<?php

namespace Bug1062ModuleLoadInclude;

function test(): void {
    module_load_include('inc', 'contained_not_initialized', 'contained_not_initialized.module_load_include');
}
