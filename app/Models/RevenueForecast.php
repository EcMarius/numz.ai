<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RevenueForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'forecast_date',
        'period_type',
        'forecast_method',
        'predicted_revenue',
        'predicted_mrr',
        'predicted_new_customers',
        'predicted_churn',
        'confidence_interval_low',
        'confidence_interval_high',
        'actual_revenue',
        'accuracy_score',
        'model_parameters',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'model_parameters' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate forecast using historical data
     */
    public static function generateForecast(
        \Carbon\Carbon $forecastDate,
        string $periodType = 'monthly',
        string $method = 'linear_regression',
        int $historicalPeriods = 12
    ): self {
        // Get historical metrics
        $historicalData = RevenueMetric::where('period_type', $periodType)
            ->orderBy('metric_date', 'desc')
            ->limit($historicalPeriods)
            ->get()
            ->reverse()
            ->values();

        if ($historicalData->isEmpty()) {
            throw new \Exception('Insufficient historical data for forecasting');
        }

        // Calculate forecast based on method
        $forecast = match($method) {
            'linear_regression' => self::linearRegressionForecast($historicalData),
            'moving_average' => self::movingAverageForecast($historicalData),
            'exponential_smoothing' => self::exponentialSmoothingForecast($historicalData),
            default => self::linearRegressionForecast($historicalData),
        };

        return self::create([
            'created_by' => auth()->id(),
            'forecast_date' => $forecastDate,
            'period_type' => $periodType,
            'forecast_method' => $method,
            'predicted_revenue' => $forecast['revenue'],
            'predicted_mrr' => $forecast['mrr'],
            'predicted_new_customers' => $forecast['customers'],
            'predicted_churn' => $forecast['churn'],
            'confidence_interval_low' => $forecast['ci_low'],
            'confidence_interval_high' => $forecast['ci_high'],
            'model_parameters' => $forecast['parameters'],
        ]);
    }

    /**
     * Linear regression forecast
     */
    protected static function linearRegressionForecast($data): array
    {
        $n = $data->count();
        $revenues = $data->pluck('total_revenue')->toArray();
        $mrrs = $data->pluck('mrr')->toArray();
        $customers = $data->pluck('new_customers')->toArray();
        $churns = $data->pluck('churn_rate')->toArray();

        // Simple linear regression: y = mx + b
        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($revenues);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $revenues[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Predict next period
        $nextPeriod = $n + 1;
        $predictedRevenue = $slope * $nextPeriod + $intercept;

        // Calculate confidence interval (simplified)
        $stdDev = self::calculateStdDev($revenues);
        $margin = 1.96 * $stdDev; // 95% confidence

        return [
            'revenue' => round(max(0, $predictedRevenue), 2),
            'mrr' => round(max(0, array_sum($mrrs) / $n + $slope), 2),
            'customers' => (int) round(max(0, array_sum($customers) / $n)),
            'churn' => round(max(0, array_sum($churns) / $n), 2),
            'ci_low' => round(max(0, $predictedRevenue - $margin), 2),
            'ci_high' => round($predictedRevenue + $margin, 2),
            'parameters' => [
                'slope' => $slope,
                'intercept' => $intercept,
                'std_dev' => $stdDev,
            ],
        ];
    }

    /**
     * Moving average forecast
     */
    protected static function movingAverageForecast($data, int $window = 3): array
    {
        $revenues = $data->pluck('total_revenue')->toArray();
        $mrrs = $data->pluck('mrr')->toArray();
        $customers = $data->pluck('new_customers')->toArray();
        $churns = $data->pluck('churn_rate')->toArray();

        // Take average of last N periods
        $recentRevenues = array_slice($revenues, -$window);
        $recentMrrs = array_slice($mrrs, -$window);
        $recentCustomers = array_slice($customers, -$window);
        $recentChurns = array_slice($churns, -$window);

        $predictedRevenue = array_sum($recentRevenues) / $window;
        $stdDev = self::calculateStdDev($recentRevenues);
        $margin = 1.96 * $stdDev;

        return [
            'revenue' => round($predictedRevenue, 2),
            'mrr' => round(array_sum($recentMrrs) / $window, 2),
            'customers' => (int) round(array_sum($recentCustomers) / $window),
            'churn' => round(array_sum($recentChurns) / $window, 2),
            'ci_low' => round(max(0, $predictedRevenue - $margin), 2),
            'ci_high' => round($predictedRevenue + $margin, 2),
            'parameters' => [
                'window' => $window,
                'std_dev' => $stdDev,
            ],
        ];
    }

    /**
     * Exponential smoothing forecast
     */
    protected static function exponentialSmoothingForecast($data, float $alpha = 0.3): array
    {
        $revenues = $data->pluck('total_revenue')->toArray();
        $mrrs = $data->pluck('mrr')->toArray();

        // Apply exponential smoothing
        $smoothed = $revenues[0];
        foreach ($revenues as $revenue) {
            $smoothed = $alpha * $revenue + (1 - $alpha) * $smoothed;
        }

        $stdDev = self::calculateStdDev($revenues);
        $margin = 1.96 * $stdDev;

        return [
            'revenue' => round($smoothed, 2),
            'mrr' => round($mrrs[count($mrrs) - 1], 2),
            'customers' => (int) round($data->last()->new_customers),
            'churn' => round($data->last()->churn_rate, 2),
            'ci_low' => round(max(0, $smoothed - $margin), 2),
            'ci_high' => round($smoothed + $margin, 2),
            'parameters' => [
                'alpha' => $alpha,
                'std_dev' => $stdDev,
            ],
        ];
    }

    /**
     * Calculate standard deviation
     */
    protected static function calculateStdDev(array $values): float
    {
        $n = count($values);
        if ($n === 0) return 0;

        $mean = array_sum($values) / $n;
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / $n);
    }

    /**
     * Update with actual results
     */
    public function updateActualResults(float $actualRevenue): void
    {
        $error = abs($actualRevenue - $this->predicted_revenue);
        $accuracyScore = $this->predicted_revenue > 0
            ? (1 - ($error / $this->predicted_revenue)) * 100
            : 0;

        $this->update([
            'actual_revenue' => $actualRevenue,
            'accuracy_score' => round(max(0, $accuracyScore), 2),
        ]);
    }

    /**
     * Get forecast accuracy for a method
     */
    public static function getMethodAccuracy(string $method, int $periods = 12): float
    {
        $forecasts = self::where('forecast_method', $method)
            ->whereNotNull('actual_revenue')
            ->orderBy('forecast_date', 'desc')
            ->limit($periods)
            ->get();

        if ($forecasts->isEmpty()) {
            return 0;
        }

        return $forecasts->avg('accuracy_score');
    }
}
