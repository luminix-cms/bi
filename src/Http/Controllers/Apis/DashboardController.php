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
use Luminix\Bi\Filters\Filter;
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
        $dashboard = new class($dbDashboard->key, $dbDashboard->name) extends Dashboard {
            protected array $registeredWidgets = [];
            protected array $registeredFilters = [];
            protected $name;
            protected $uriKey;
            protected $model;
            
            public function __construct($key, $name)
            {
                $this->uriKey = $key;
                $this->name = $name;
                $this->model = BiDashboard::class;
            }
    
            public function addWidget($widget): Widget
            {
                $this->registeredWidgets[] = $widget;
                return $widget;
            }
    
            public function addFilter($filter): Filter
            {
                $this->registeredFilters[] = $filter;
                return $filter;
            }
    
            public function widgets()
            {
                return $this->registeredWidgets;
            }
    
            public function filters()
            {
                return $this->registeredFilters;
            }
        };
    
        // Carrega widgets
        foreach ($dbDashboard->widgets as $dbWidget) {
            // Verifica se há uma classe específica para o widget
            $widgetClass = $dbWidget->type ?? 'Luminix\Bi\Widgets\GenericWidget';
            
            if (!class_exists($widgetClass)) {
                $widgetClass = 'Luminix\Bi\Widgets\GenericWidget';
            }
    
            // Cria o widget usando a classe específica ou a genérica
            $widget = $widgetClass::create($dbWidget->key, $dbWidget->name);
            
            // Configura propriedades do widget
            if ($dbWidget->component) {
                $widget->component = $dbWidget->component;
            }
            
            if ($dbWidget->width) {
                $widget->width = $dbWidget->width;
            }
            
            if ($dbWidget->extra) {
                $widget->extra = [$dbWidget->extra];
            }
    
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
            if ($dbWidget->metrics->isNotEmpty()) {
                $metrics = $dbWidget->metrics->map(function ($dbMetric) {
                    $metricClass = $dbMetric->type;
                    if (class_exists($metricClass)) {
                        return $metricClass::create($dbMetric->key, $dbMetric->name);
                    }
                    return null;
                })->filter();
    
                $widget->metrics($metrics);
            }
    
            $dashboard->addWidget($widget);
        }
    
        // Carrega filtros
        foreach ($dbDashboard->filters as $dbFilter) {
            $filterClass = $dbFilter->type ?? 'Luminix\Bi\Filters\GenericFilter';
            
            if (!class_exists($filterClass)) {
                $filterClass = 'Luminix\Bi\Filters\GenericFilter';
            }
    
            $filter = $filterClass::create($dbFilter->key, $dbFilter->name);
    
            if ($dbFilter->component) {
                $filter->component = $dbFilter->component;
            }
    
            if ($dbFilter->column) {
                $filter->column = $dbFilter->column;
            }
    
            if ($dbFilter->relation) {
                $filter->relation = $dbFilter->relation;
            }
    
            if ($dbFilter->options) {
                $filter->options = [$dbFilter->options];
            }
    
            $dashboard->addFilter($filter);
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
