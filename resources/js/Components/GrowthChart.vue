<template>
  <div class="glass-card p-6 rounded-3xl border border-indigo-500/15 relative overflow-hidden">
    <div class="section-header mb-4">
      <span class="section-title">📈 7-Day Growth Trend</span>
      <div class="section-header-line"></div>
    </div>
    <div class="relative" style="height: 220px;">
      <canvas ref="canvasEl"></canvas>
    </div>
    <div class="flex items-center gap-4 mt-4 text-[11px]">
      <span class="flex items-center gap-1.5 text-indigo-400 font-semibold"><span class="w-2 h-2 rounded-full bg-indigo-500"></span> New Users</span>
      <span class="flex items-center gap-1.5 text-emerald-400 font-semibold"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Completed Tasks</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);

const props = defineProps({
  chartData: {
    type: Object,
    default: () => ({ labels: [], newUsers: [], completedTasks: [] }),
  },
});

const canvasEl = ref(null);
let chartInstance = null;

const buildChart = () => {
  if (!canvasEl.value) return;
  const ctx = canvasEl.value.getContext('2d');

  const indigoGradient = ctx.createLinearGradient(0, 0, 0, 220);
  indigoGradient.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
  indigoGradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

  const emeraldGradient = ctx.createLinearGradient(0, 0, 0, 220);
  emeraldGradient.addColorStop(0, 'rgba(16, 185, 129, 0.30)');
  emeraldGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.chartData.labels,
      datasets: [
        {
          label: 'New Users',
          data: props.chartData.newUsers,
          borderColor: '#818cf8',
          backgroundColor: indigoGradient,
          borderWidth: 2.5,
          tension: 0.4,
          fill: true,
          pointRadius: 3,
          pointBackgroundColor: '#818cf8',
          pointBorderColor: '#0a0e1a',
        },
        {
          label: 'Completed Tasks',
          data: props.chartData.completedTasks,
          borderColor: '#34d399',
          backgroundColor: emeraldGradient,
          borderWidth: 2.5,
          tension: 0.4,
          fill: true,
          pointRadius: 3,
          pointBackgroundColor: '#34d399',
          pointBorderColor: '#0a0e1a',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(8, 12, 28, 0.95)',
          borderColor: 'rgba(99, 102, 241, 0.3)',
          borderWidth: 1,
          titleColor: '#f0f4ff',
          bodyColor: '#a5b4fc',
          padding: 10,
          cornerRadius: 10,
        },
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#7680a0', font: { size: 10 } },
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#7680a0', font: { size: 10 }, precision: 0 },
        },
      },
    },
  });
};

onMounted(buildChart);

watch(
  () => props.chartData,
  () => {
    if (chartInstance) {
      chartInstance.data.labels = props.chartData.labels;
      chartInstance.data.datasets[0].data = props.chartData.newUsers;
      chartInstance.data.datasets[1].data = props.chartData.completedTasks;
      chartInstance.update();
    }
  },
  { deep: true }
);

onBeforeUnmount(() => {
  chartInstance?.destroy();
});
</script>
