<?php

namespace App\Http\Controllers\Api\FrontendV1;

use App\Http\Controllers\Controller;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class UserController extends Controller
{

    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    public function token(): DataResponse
    {
        $user = auth()->user();
        $user->tokens()->where('source', 'frontend-auth-token')->delete();
        $token = $user->createToken('frontend-auth-token');

        $token->accessToken->source = 'frontend-auth-token';
        $token->accessToken->save();

        return DataResponse::make($token->accessToken)
            ->didCreate()
            ->withMeta([
                'plain_text_token' => $token->plainTextToken
            ]);
    }

    public function me(): DataResponse
    {
        return DataResponse::make(auth()->user());
    }
}
