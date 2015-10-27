<?php

namespace Fastpress\Arrow;

use Fastpress\Arrow\Builder\FluentBuilderTrait;

/**
 * FluentModel adds the FluentBuilderTrait to Model.
 *
 * This is just a shortcut for using the trait.
 */
class FluentModel extends Model
{
    use FluentBuilderTrait;
}
