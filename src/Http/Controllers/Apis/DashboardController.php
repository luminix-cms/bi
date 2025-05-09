<?php

namespace Luminix\Bi\Http\Controllers\Apis;

use Illuminate\Http\Request;
use Luminix\Bi\Models\BiDashboard;
use Luminix\Bi\Models\BiWidget;
use Luminix\Bi\Models\BiDimension;
use Luminix\Bi\Models\BiMetric;
use Luminix\Bi\Models\BiFilter;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Http\Controllers\BaseController;
use Luminix\Bi\Models\BiWidgetData;
use Luminix\Bi\Widgets\Widget;

class DashboardController extends BaseController
{
    protected $key;
    protected $name;
    public function getDashboards(BiRequest $request)
    {
        // Carrega do banco de dados
        $dashboards = BiDashboard::all()->map(function ($dbDashboard) {
            return $this->createDashboardFromDb($dbDashboard);
        });

        return [
            'status' => 200,
            'data'   => $dashboards
        ];
    }

    public function getWidgets($dashboard, BiRequest $request)
    {
        $dbDashboard = BiDashboard::where('key', $dashboard)->firstOrFail();
        $dashboard = $this->createDashboardFromDb($dbDashboard);

        return [
            'status' => 200,
            'data'   => $dashboard
        ];
    }

    protected function createDashboardFromDb(BiDashboard $dbDashboard)
    {
        // $dashboard = new Dashboard($dbDashboard->key, $dbDashboard->name);
        $dashboard = new class($dbDashboard->key, $dbDashboard->name) extends Dashboard {
            protected array $registeredWidgets = [];
            protected $name;
            protected $uriKey;
            public function __construct($key, $name)
            {
                $this->uriKey = $key;
                $this->name = $name;
            }

            public function addWidget(string $key, string $name): Widget
            {
                $widget = new Widget($key, $name);
                // $widget = Widget::make($key, $name);
                $this->registeredWidgets[] = $widget;
                return $widget;
            }

            public function widgets()
            {
                return $this->registeredWidgets;
            }

            public function filters()
            {
                return [];
            }
        };

        // Carrega widgets
        foreach ($dbDashboard->widgets as $dbWidget) {
            // $widget = $dashboard->addWidget($dbWidget->key, $dbWidget->name);

            $widget = $dbWidget;

            // Configura dimensões
            if ($dbWidget->dimensions->isNotEmpty()) {
                $dimensions = $dbWidget->dimensions->map(function ($dbDimension) {
                    $dimensionClass = $dbDimension->type;
                    if (class_exists($dimensionClass)) {
                        return $dimensionClass::create($dbDimension->key, $dbDimension->name);
                    }
                    return null;
                })->filter();

                $widget->dimensions($dimensions);
            }



            // Configura métricas
            foreach ($dbWidget->metrics as $dbMetric) {
                $metricClass = $dbMetric->type;
                if (class_exists($metricClass)) {
                    $metric = $metricClass::create($dbMetric->key, $dbMetric->name);
                    $widget->metrics(collect([$metric]));
                }


                // Configurações adicionais
                if ($dbWidget->component) {
                    $widget->component = $dbWidget->component;
                }

                if ($dbWidget->width) {
                    $widget->width = $dbWidget->width;
                }

                if ($dbWidget->extra) {
                    $widget->extra = $dbWidget->extra;
                }
            }

            $dashboard->widgets(collect([$widget]));
        }


        // Carrega filtros
        foreach ($dbDashboard->filters as $dbFilter) {
            // $filter = $dashboard->addFilter($dbFilter->key, $dbFilter->name);
            $filter = $dbFilter;

            if ($dbFilter->component) {
                $filter->component = $dbFilter->component;
            }

            if ($dbFilter->column) {
                $filter->column = $dbFilter->column;
            }

            if ($dbFilter->relation) {
                $filter->relation = $dbFilter->relation;
            }

            $dashboard->filters(collect([$filter]));
        }


        return $dashboard;
    }

    public function createWidget(Request $request)
    {
        $request->validate([
            'dashboard_id' => 'required',
            'widgets' => 'required|array',
        ]);

        $dbDashboard = BiDashboard::firstOrCreate(
            ['key' => $request->input('dashboard_id')],
            ['name' => $request->input('dashboard_name', 'Novo Dashboard')]
        );

        $results = [];

        foreach ($request->input('widgets') as $widgetData) {
            try {
                $dbWidget = BiWidget::create([
                    'dashboard_id' => $dbDashboard->id,
                    'key' => $widgetData['key'],
                    'name' => $widgetData['name'],
                    'component' => $widgetData['component'] ?? null,
                    'width' => $widgetData['width'] ?? 12,
                    'extra' => $widgetData['extra'] ?? null,
                ]);

                // Salva dimensões
                if (isset($widgetData['dimensions'])) {
                    foreach ($widgetData['dimensions'] as $dimension) {
                        BiDimension::create([
                            'widget_id' => $dbWidget->id,
                            'key' => $dimension['key'],
                            'name' => $dimension['name'],
                            'type' => $dimension['type'] ?? 'Luminix\Bi\Dimensions\GenericDimension',
                            'options' => $dimension['options'] ?? null,
                        ]);
                    }
                }

                // Salva métricas
                if (isset($widgetData['metrics'])) {
                    foreach ($widgetData['metrics'] as $metric) {
                        BiMetric::create([
                            'widget_id' => $dbWidget->id,
                            'key' => $metric['key'],
                            'name' => $metric['name'],
                            'type' => $metric['type'] ?? 'Luminix\Bi\Metrics\GenericMetric',
                            'options' => $metric['options'] ?? null,
                        ]);
                    }
                }

                $results[] = ['status' => 'success', 'key' => $widgetData['key']];
            } catch (\Exception $e) {
                $results[] = ['status' => 'error', 'key' => $widgetData['key'], 'message' => $e->getMessage()];
            }
        }

        // Salva filtros
        if ($request->has('filters')) {
            foreach ($request->input('filters') as $filter) {
                BiFilter::create([
                    'dashboard_id' => $dbDashboard->id,
                    'key' => $filter['key'],
                    'name' => $filter['name'],
                    'type' => $filter['type'] ?? 'Luminix\Bi\Filters\GenericFilter',
                    'component' => $filter['component'] ?? null,
                    'column' => $filter['column'] ?? null,
                    'relation' => $filter['relation'] ?? null,
                    'options' => $filter['options'] ?? null,
                ]);
            }
        }

        return [
            'status' => 200,
            'message' => 'Widgets processados',
            'data' => [
                'dashboard_id' => $dbDashboard->key,
                'results' => $results
            ]
        ];
    }

    public function loadWidgetsFromDb(Request $request)
    {
        // Esta função agora pode ser simplificada já que tudo está no banco
        return $this->getDashboards(new BiRequest($request));
    }

    public function getWidget($dashboard, $widget, BiRequest $request)
    {
        $dashboard = $this->dashboardResolver->find($dashboard) ?? abort(404);
        $widget = $dashboard->findWidgetOrFail($widget);

        // Busca dados associados ao widget
        $widgetData = BiWidgetData::whereHas('widget', function ($q) use ($widget) {
            $q->where('key', $widget->key);
        })->first();

        return [
            'status' => 200,
            'data' => [
                'widget' => $widget,
                'widget_data' => $widgetData ? $widgetData->data : null,
                'data_source' => $widgetData ? [
                    'type' => $widgetData->source_type,
                    'details' => $widgetData->source_details,
                    'last_updated' => $widgetData->last_updated
                ] : null
            ]
        ];
    }
}
