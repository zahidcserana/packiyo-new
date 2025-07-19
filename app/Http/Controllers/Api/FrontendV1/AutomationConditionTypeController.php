<?php

namespace App\Http\Controllers\Api\FrontendV1;

use App\Http\Controllers\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

/**
 * @todo TODO: This may not be needed at all, see https://laraveljsonapi.io/docs/1.0/routing/writing-actions.html#introduction
 */
class AutomationConditionTypeController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
}
