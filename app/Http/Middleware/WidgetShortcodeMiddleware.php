<?php

namespace App\Http\Middleware;

use App\Models\UserWidget;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WidgetShortcodeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!method_exists($response, 'content')) {
            return $response;
        }

        if (Auth::user()) {
            $content = $response->content();

            $widgetList = UserWidget::WIDGET_LIST;

            foreach ($widgetList as $shortcode => $data) {
                $renderedWidget = view($data['view'])->render();

                if (get_class($response) == JsonResponse::class) {
                    $renderedWidget = substr(json_encode($renderedWidget), 1, -1);
                }

                $content = str_replace($shortcode, $renderedWidget, $content);
            }

            $response->setContent($content);
        }


        return $response;
    }
}
