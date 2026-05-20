<?php

namespace Luminix\Bi\Http\Controllers\Apis;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Http\Controllers\BaseController;

class WidgetController extends BaseController
{
    public function getWidget($dashboard, $widgetKey, BiRequest $request)
    {
        if (Config::get('luminix.bi.debug', false)) {
            DB::enableQueryLog();
        }

        $dashboard = $this->dashboardResolver->find($dashboard) ?? abort(404);

        $widget = $dashboard->findWidgetOrFail($widgetKey);
        $response = [
            'status' => 200,
            'data'   => $widget->data($dashboard, $request)
        ];

        if (Config::get('luminix.bi.debug', false)) {
            $response['debug'] = DB::getQueryLog();
        }

        return $response;
    }

    public function download($dashboard, $widgetKey, BiRequest $request)
    {

        $dashboard = $this->dashboardResolver->find($dashboard) ?? abort(404);

        abort_unless($dashboard->hasCsvOutput(), 404);

        $widget = $dashboard->findWidgetOrFail($widgetKey);

        $data = $widget->data($dashboard, $request);

        $headers = [
            'Content-type'        => 'text/plain',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
            'Content-Disposition' => 'attachment; filename=' . Str::slug($widget->name) . '.csv'
        ];

        return response()->stream(function () use ($data) {
            $file = fopen('php://output', 'w');

            fputcsv($file, array_keys(get_object_vars($data[0])));

            foreach ($data as $row) {
                fputcsv($file, get_object_vars($row));
            }

            fclose($file);
        }, 200, $headers);
    }
}
