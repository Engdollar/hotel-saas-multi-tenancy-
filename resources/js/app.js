import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Chart = Chart;

window.loadClassicEditor = () => {
	if (window.ClassicEditor) {
		return Promise.resolve(window.ClassicEditor);
	}

	if (window.__classicEditorLoader) {
		return window.__classicEditorLoader;
	}

	window.__classicEditorLoader = new Promise((resolve, reject) => {
		const existingScript = document.querySelector('script[data-ckeditor-classic]');

		if (existingScript) {
			existingScript.addEventListener('load', () => resolve(window.ClassicEditor));
			existingScript.addEventListener('error', () => reject(new Error('Unable to load CKEditor.')));
			return;
		}

		const script = document.createElement('script');
		script.src = 'https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js';
		script.async = true;
		script.dataset.ckeditorClassic = 'true';
		script.onload = () => {
			if (window.ClassicEditor) {
				resolve(window.ClassicEditor);
				return;
			}

			reject(new Error('CKEditor loaded without exposing the ClassicEditor build.'));
		};
		script.onerror = () => reject(new Error('Unable to load CKEditor.'));
		document.head.append(script);
	});

	return window.__classicEditorLoader;
};

class SupportImageUploadAdapter {
	constructor(loader, uploadUrl, csrfToken) {
		this.loader = loader;
		this.uploadUrl = uploadUrl;
		this.csrfToken = csrfToken;
		this.controller = null;
	}

	upload() {
		return this.loader.file.then((file) => new Promise((resolve, reject) => {
			const data = new FormData();
			data.append('upload', file);

			this.controller = new AbortController();

			fetch(this.uploadUrl, {
				method: 'POST',
				body: data,
				headers: {
					'X-CSRF-TOKEN': this.csrfToken,
					'X-Requested-With': 'XMLHttpRequest',
				},
				signal: this.controller.signal,
				credentials: 'same-origin',
			})
				.then(async (response) => {
					const payload = await response.json().catch(() => ({}));

					if (!response.ok || !payload.url) {
						throw new Error(payload.message || 'Unable to upload image.');
					}

					resolve({ default: payload.url });
				})
				.catch((error) => {
					reject(error);
				});
		}));
	}

	abort() {
		this.controller?.abort();
	}
}

window.initializeSupportEditor = async (textarea, {
	uploadUrl,
} = {}) => {
	if (!textarea || !uploadUrl || textarea.dataset.editorInitialized === 'true') {
		return null;
	}

	const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
	const ClassicEditor = await window.loadClassicEditor();

	const editor = await ClassicEditor.create(textarea, {
		placeholder: 'Write an update and paste screenshots directly into the message...',
	});

	editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new SupportImageUploadAdapter(loader, uploadUrl, csrfToken);

	editor.model.document.on('change:data', () => {
		textarea.value = editor.getData();
	});

	textarea.dataset.editorInitialized = 'true';
	textarea.__supportEditor = editor;

	return editor;
};

window.startTicketLiveStream = ({
	endpoint,
	fingerprint,
	onUpdate,
	intervalMs = 8000,
} = {}) => {
	if (!endpoint || typeof onUpdate !== 'function') {
		return null;
	}

	let currentFingerprint = fingerprint ?? '';
	let stopped = false;

	const poll = async () => {
		if (stopped || document.visibilityState === 'hidden') {
			return;
		}

		const url = new URL(endpoint, window.location.origin);
		url.searchParams.set('fingerprint', currentFingerprint);

		try {
			const response = await fetch(url.toString(), {
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
			});

			if (!response.ok) {
				return;
			}

			const payload = await response.json();

			if (payload?.has_updates) {
				currentFingerprint = payload.fingerprint ?? currentFingerprint;
				onUpdate(payload);
			}
		} catch (_error) {
			// Keep polling on transient network errors.
		}
	};

	const timer = window.setInterval(poll, intervalMs);
	poll();

	const onVisibilityChange = () => {
		if (document.visibilityState === 'visible') {
			poll();
		}
	};

	document.addEventListener('visibilitychange', onVisibilityChange);

	return {
		stop() {
			stopped = true;
			window.clearInterval(timer);
			document.removeEventListener('visibilitychange', onVisibilityChange);
		},
	};
};

window.resolveDarkState = (mode) => {
	if (mode === 'system') {
		return window.matchMedia('(prefers-color-scheme: dark)').matches;
	}

	return mode === 'dark';
};

window.applyThemePreference = ({ mode, preset }) => {
	document.documentElement.dataset.themePreset = preset;
	document.documentElement.classList.toggle('dark', window.resolveDarkState(mode));
};

window.evaluatePasswordStrength = (value = '') => {
	const password = value ?? '';

	if (!password.length) {
		return {
			score: 0,
			label: 'Start typing',
			hint: 'Use 8 or more characters with a mix of letters, numbers, and symbols.',
			tone: 'idle',
		};
	}

	let score = 0;

	if (password.length >= 8) {
		score += 1;
	}

	if (/[A-Z]/.test(password) && /[a-z]/.test(password)) {
		score += 1;
	}

	if (/\d/.test(password)) {
		score += 1;
	}

	if (/[^A-Za-z0-9]/.test(password)) {
		score += 1;
	}

	if (password.length >= 12 && score < 4) {
		score += 1;
	}

	score = Math.min(score, 4);

	if (score <= 1) {
		return {
			score,
			label: 'Weak',
			hint: 'Add more length and combine upper, lower, numeric, and special characters.',
			tone: 'weak',
		};
	}

	if (score === 2) {
		return {
			score,
			label: 'Fair',
			hint: 'Good start. Add another character type or extend the password.',
			tone: 'weak',
		};
	}

	if (score === 3) {
		return {
			score,
			label: 'Good',
			hint: 'This is solid. A longer passphrase would make it even better.',
			tone: 'good',
		};
	}

	return {
		score,
		label: 'Strong',
		hint: 'Strong password. It is long and well mixed.',
		tone: 'strong',
	};
};

window.adminSwal = Swal.mixin({
	buttonsStyling: false,
	customClass: {
		popup: 'theme-swal-popup',
		title: 'theme-swal-title',
		htmlContainer: 'theme-swal-body',
		confirmButton: 'btn-danger theme-swal-confirm',
		cancelButton: 'btn-secondary theme-swal-cancel',
	},
});

window.confirmDestructiveAction = async ({
	title = 'Delete item?',
	text = 'This action cannot be undone.',
	confirmButtonText = 'Delete',
	cancelButtonText = 'Cancel',
} = {}) => {
	const result = await window.adminSwal.fire({
		icon: 'warning',
		title,
		text,
		showCancelButton: true,
		confirmButtonText,
		cancelButtonText,
		reverseButtons: true,
		focusCancel: true,
	});

	return result.isConfirmed;
};

window.dispatchAppToast = (type, message) => {
	window.dispatchEvent(new CustomEvent('app-toast', { detail: { type, message } }));
};

window.startAppLoading = (message = 'Loading workspace...') => {
	window.dispatchEvent(new CustomEvent('app-loading-start', { detail: { message } }));
};

window.stopAppLoading = () => {
	window.dispatchEvent(new CustomEvent('app-loading-stop'));
};

window.resolveDownloadFilename = (response, fallback = 'download') => {
	const disposition = response.headers.get('content-disposition') ?? '';
	const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
	const basicMatch = disposition.match(/filename="?([^";]+)"?/i);

	if (utf8Match?.[1]) {
		return decodeURIComponent(utf8Match[1]);
	}

	if (basicMatch?.[1]) {
		return basicMatch[1];
	}

	return fallback;
};

window.downloadFile = async ({ url, filename = 'download', message = 'Preparing download...' }) => {
	window.startAppLoading(message);

	try {
		const response = await fetch(url, {
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
		});

		if (!response.ok) {
			throw new Error('Unable to download the file.');
		}

		const blob = await response.blob();
		const objectUrl = window.URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = objectUrl;
		link.download = window.resolveDownloadFilename(response, filename);
		document.body.append(link);
		link.click();
		link.remove();

		window.setTimeout(() => {
			window.URL.revokeObjectURL(objectUrl);
		}, 1000);
	} finally {
		window.stopAppLoading();
	}
};

window.themePreviewStorageKey = 'theme-preview-default-signature';
window.sidebarCollapseStorageKey = 'admin-sidebar-collapsed';
window.dashboardChartInstances = new Map();

const truncateChartLabel = (value, maxLength = 14) => {
	const label = `${value ?? ''}`.trim();

	if (label.length <= maxLength) {
		return label;
	}

	return `${label.slice(0, Math.max(0, maxLength - 1)).trimEnd()}…`;
};

const ensureDashboardLegend = (canvas, resolvedType) => {
	const panel = canvas.closest('[data-dashboard-chart]');

	if (!panel) {
		return null;
	}

	panel.dataset.chartType = resolvedType;

	let legend = panel.querySelector('[data-chart-legend]');

	if (resolvedType !== 'pie') {
		legend?.remove();
		return null;
	}

	if (!legend) {
		legend = document.createElement('div');
		legend.className = 'chart-card-legend';
		legend.dataset.chartLegend = 'true';
		const shell = panel.querySelector('.chart-shell');
		shell?.parentNode?.insertBefore(legend, shell);
	}

	return legend;
};

const renderDashboardLegend = (legend, labels, colors) => {
	if (!legend) {
		return;
	}

	legend.innerHTML = labels.map((label, index) => `
		<div class="chart-legend-item">
			<span class="chart-legend-swatch" style="--legend-swatch:${colors[index]};"></span>
			<span class="chart-legend-label">${truncateChartLabel(label, 20)}</span>
		</div>
	`).join('');
};

Chart.register({
	id: 'dashboardPieCallouts',
	afterDatasetsDraw(chart, _args, options) {
		if (chart.config.type !== 'pie' || options?.enabled === false) {
			return;
		}

		const dataset = chart.data.datasets?.[0];
		const meta = chart.getDatasetMeta(0);
		const labels = chart.data.labels ?? [];
		const colors = Array.isArray(dataset?.backgroundColor) ? dataset.backgroundColor : [];

		if (!dataset || !meta?.data?.length) {
			return;
		}

		const { ctx } = chart;
		const textColor = options?.textColor ?? '#52607a';
		const font = options?.font ?? '500 12px Instrument Sans, sans-serif';

		ctx.save();
		ctx.font = font;
		ctx.fillStyle = textColor;
		ctx.lineWidth = options?.lineWidth ?? 1.5;
		ctx.textBaseline = 'middle';

		meta.data.forEach((arc, index) => {
			const value = Number(dataset.data?.[index] ?? 0);

			if (!Number.isFinite(value) || value <= 0) {
				return;
			}

			const angle = (arc.startAngle + arc.endAngle) / 2;
			const startX = arc.x + Math.cos(angle) * (arc.outerRadius - 2);
			const startY = arc.y + Math.sin(angle) * (arc.outerRadius - 2);
			const middleX = arc.x + Math.cos(angle) * (arc.outerRadius + 14);
			const middleY = arc.y + Math.sin(angle) * (arc.outerRadius + 14);
			const alignRight = Math.cos(angle) >= 0;
			const endX = middleX + (alignRight ? 24 : -24);
			const labelX = endX + (alignRight ? 8 : -8);
			const color = colors[index] ?? '#94a3b8';

			ctx.strokeStyle = color;
			ctx.beginPath();
			ctx.moveTo(startX, startY);
			ctx.lineTo(middleX, middleY);
			ctx.lineTo(endX, middleY);
			ctx.stroke();

			ctx.textAlign = alignRight ? 'left' : 'right';
			ctx.fillText(truncateChartLabel(labels[index], 12), labelX, middleY);
		});

		ctx.restore();
	},
});

window.chartPalette = () => {
	const styles = getComputedStyle(document.documentElement);

	return [
		styles.getPropertyValue('--accent').trim(),
		styles.getPropertyValue('--accent-strong').trim(),
		styles.getPropertyValue('--text-primary').trim(),
		styles.getPropertyValue('--text-soft').trim(),
		'#d67f5f',
		'#7c9e8d',
	];
};

window.expandChartPalette = (count = 0) => {
	const palette = window.chartPalette();
	const colors = [];

	for (let index = 0; index < count; index += 1) {
		colors.push(palette[index % palette.length]);
	}

	return colors;
};

window.renderLoadingRow = (columns, message = 'Loading records...') => `
	<tr>
		<td colspan="${columns}" class="px-4 py-10 text-center">
			<div class="inline-flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
				<span class="loader-spinner is-inline"></span>
				<span>${message}</span>
			</div>
		</td>
	</tr>
`;

window.initDashboardCharts = (configs = {}) => {
	Object.entries(configs).forEach(([key, config]) => {
		const canvas = document.querySelector(config.canvas);

		if (!canvas || window.dashboardChartInstances.has(key)) {
			return;
		}

		const shell = canvas.closest('.chart-shell');
		const styles = getComputedStyle(document.documentElement);
		const palette = window.expandChartPalette(Math.max(config.labels?.length ?? 0, config.datasets?.length ?? 0, 6));

		const buildChart = () => {
			const resolvedType = config.type ?? 'line';
			const textMuted = styles.getPropertyValue('--text-muted').trim();
			const textPrimary = styles.getPropertyValue('--text-primary').trim();
			const borderColor = styles.getPropertyValue('--panel-border').trim();
			const panelBackground = styles.getPropertyValue('--panel-bg').trim();
			const legend = ensureDashboardLegend(canvas, resolvedType);
			const lineGradient = canvas.getContext('2d')?.createLinearGradient(0, 0, 0, canvas.height);

			if (lineGradient) {
				lineGradient.addColorStop(0, `${palette[0]}26`);
				lineGradient.addColorStop(1, `${palette[0]}00`);
			}

			const chart = new Chart(canvas, {
				type: resolvedType,
				data: {
					labels: config.labels,
					datasets: config.datasets.map((dataset, index) => ({
						borderColor: resolvedType === 'pie' ? panelBackground : palette[index % palette.length],
						backgroundColor: resolvedType === 'pie'
							? palette.slice(0, config.labels?.length ?? palette.length).map((color) => `${color}E0`)
							: resolvedType === 'bar'
								? palette.slice(0, config.labels?.length ?? palette.length).map((color) => `${color}D9`)
									: lineGradient ?? `${palette[index % palette.length]}22`,
						fill: resolvedType === 'line',
							tension: resolvedType === 'line' ? 0.42 : undefined,
							pointRadius: resolvedType === 'line' ? 4.5 : undefined,
							pointHoverRadius: resolvedType === 'line' ? 6 : undefined,
						pointHitRadius: resolvedType === 'line' ? 18 : undefined,
							pointBackgroundColor: resolvedType === 'line' ? '#ffffff' : undefined,
							pointBorderColor: resolvedType === 'line' ? palette[index % palette.length] : undefined,
							pointBorderWidth: resolvedType === 'line' ? 2.5 : undefined,
							borderWidth: resolvedType === 'line' ? 2.75 : resolvedType === 'pie' ? 0 : 0,
						borderRadius: resolvedType === 'bar' ? 10 : undefined,
						maxBarThickness: resolvedType === 'bar' ? 34 : undefined,
						...dataset,
					})),
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
						layout: {
							padding: resolvedType === 'pie'
								? { top: 30, right: 58, bottom: 18, left: 58 }
								: { top: 6, right: 6, bottom: 0, left: 0 },
						},
					interaction: {
						mode: 'index',
						intersect: false,
					},
					plugins: {
							dashboardPieCallouts: {
								enabled: resolvedType === 'pie',
								textColor: textPrimary,
							},
						legend: {
								display: resolvedType === 'line' ? config.datasets.length > 1 : false,
								position: 'bottom',
							labels: {
								color: textMuted,
								usePointStyle: true,
									pointStyle: 'circle',
								boxWidth: 10,
								padding: 16,
							},
						},
						tooltip: {
							backgroundColor: panelBackground,
							titleColor: styles.getPropertyValue('--text-primary').trim(),
							bodyColor: textMuted,
							borderColor,
							borderWidth: 1,
							displayColors: resolvedType !== 'line',
						},
					},
					scales: resolvedType === 'line' || resolvedType === 'bar' ? {
						x: {
							grid: {
									display: false,
									color: resolvedType === 'bar' ? 'transparent' : `${borderColor}28`,
								drawBorder: false,
							},
							ticks: {
								color: textMuted,
								padding: 10,
									maxRotation: 50,
									minRotation: 35,
							},
						},
						y: {
							beginAtZero: true,
							grid: {
									color: `${borderColor}38`,
								drawBorder: false,
							},
							ticks: {
								color: textMuted,
								padding: 10,
								precision: 0,
							},
						},
					} : undefined,
				},
			});

			if (resolvedType === 'pie') {
				renderDashboardLegend(
					legend,
					config.labels ?? [],
					Array.isArray(chart.data.datasets?.[0]?.backgroundColor) ? chart.data.datasets[0].backgroundColor : [],
				);
			}

			window.dashboardChartInstances.set(key, chart);
			shell?.classList.add('is-ready');
		};

		if ('IntersectionObserver' in window) {
			const observer = new IntersectionObserver((entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						buildChart();
						observer.disconnect();
					}
				});
			}, { threshold: 0.2 });

			observer.observe(canvas);
			return;
		}

		buildChart();
	});
};

window.initServerTable = (config) => {
	const table = document.querySelector(config.selector);

	if (!table) {
		return;
	}

	const tbody = table.querySelector('tbody');
	const pagination = document.querySelector(config.paginationSelector);
	const searchInput = document.querySelector(config.searchSelector);
	const info = document.querySelector(config.infoSelector);
	const headers = Array.from(table.querySelectorAll('[data-column]'));
	const exportButtons = config.exportSelector ? Array.from(document.querySelectorAll(config.exportSelector)) : [];

	const state = {
		draw: 1,
		start: 0,
		length: config.length ?? 10,
		search: '',
		orderColumn: config.defaultOrder?.column ?? 0,
		orderDir: config.defaultOrder?.dir ?? 'desc',
	};

	const buildQuery = () => {
		const params = new URLSearchParams({
			draw: state.draw,
			start: state.start,
			length: state.length,
			'search[value]': state.search,
			'order[0][column]': state.orderColumn,
			'order[0][dir]': state.orderDir,
		});

		config.columns.forEach((column, index) => {
			params.append(`columns[${index}][data]`, column.data);
			params.append(`columns[${index}][name]`, column.name ?? column.data);
			params.append(`columns[${index}][searchable]`, column.searchable ?? true);
			params.append(`columns[${index}][orderable]`, column.orderable ?? true);
		});

		return params.toString();
	};

	const renderRows = (rows) => {
		tbody.innerHTML = rows.map((row) => `
			<tr class="border-b border-slate-200/80 dark:border-slate-800/80">
				${config.columns.map((column) => `<td class="px-4 py-3 align-top text-[13px] text-slate-700 dark:text-slate-200">${row[column.data] ?? ''}</td>`).join('')}
			</tr>
		`).join('');
	};

	const renderPagination = (response) => {
		if (!pagination) {
			return;
		}

		const currentPage = Math.floor(state.start / state.length) + 1;
		const totalPages = Math.max(Math.ceil(response.recordsFiltered / state.length), 1);

		pagination.innerHTML = `
			<button type="button" class="rounded-full border border-slate-200 px-4 py-2 text-sm disabled:opacity-50 dark:border-slate-700" ${currentPage === 1 ? 'disabled' : ''} data-page="prev">Previous</button>
			<span class="text-sm text-slate-500 dark:text-slate-400">Page ${currentPage} of ${totalPages}</span>
			<button type="button" class="rounded-full border border-slate-200 px-4 py-2 text-sm disabled:opacity-50 dark:border-slate-700" ${currentPage === totalPages ? 'disabled' : ''} data-page="next">Next</button>
		`;

		pagination.querySelector('[data-page="prev"]')?.addEventListener('click', () => {
			state.start = Math.max(0, state.start - state.length);
			load();
		});

		pagination.querySelector('[data-page="next"]')?.addEventListener('click', () => {
			state.start += state.length;
			load();
		});
	};

	const load = async () => {
		tbody.innerHTML = window.renderLoadingRow(config.columns.length);

		const response = await fetch(`${config.endpoint}?${buildQuery()}`, {
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				Accept: 'application/json',
			},
		}).then((result) => result.json());

		renderRows(response.data);
		renderPagination(response);

		if (info) {
			info.textContent = `${response.recordsFiltered} records found`;
		}

		state.draw += 1;
	};

	exportButtons.forEach((button) => {
		button.addEventListener('click', async () => {
			const url = new URL(button.dataset.exportUrl, window.location.origin);
			const query = searchInput?.value?.trim();

			if (query) {
				url.searchParams.set('search', query);
			}

			try {
				await window.downloadFile({
					url: url.toString(),
					filename: `export.${button.dataset.format || 'csv'}`,
					message: button.dataset.loadingMessage || 'Preparing export...',
				});
			} catch (error) {
				window.dispatchAppToast('error', error.message);
			}
		});
	});

	headers.forEach((header) => {
		header.addEventListener('click', () => {
			const columnIndex = Number(header.dataset.index);

			if (state.orderColumn === columnIndex) {
				state.orderDir = state.orderDir === 'asc' ? 'desc' : 'asc';
			} else {
				state.orderColumn = columnIndex;
				state.orderDir = 'asc';
			}

			load();
		});
	});

	searchInput?.addEventListener('input', (event) => {
		state.search = event.target.value;
		state.start = 0;
		load();
	});

	load();
};

document.addEventListener('alpine:init', () => {
	Alpine.data('installerWizard', (config = {}) => ({
		step: config.initialStep ?? 1,
		maxStep: 5,
		requirementsPass: config.requirementsPass ?? false,
		stepLabels: config.stepLabels ?? [],
		databaseTestEndpoint: config.databaseTestEndpoint ?? '',
		databaseStatus: {
			state: 'idle',
			message: 'Run a live connection test before the final step.',
		},
		init() {
			this.step = this.normalizeStep(this.step);
		},
		normalizeStep(value) {
			const numeric = Number.parseInt(value, 10);

			if (Number.isNaN(numeric)) {
				return 1;
			}

			return Math.min(this.maxStep, Math.max(1, numeric));
		},
		goToStep(value) {
			const target = this.normalizeStep(value);

			if (target === 1 || this.requirementsPass) {
				this.step = target;
			}
		},
		nextStep() {
			if (!this.canAdvance) {
				return;
			}

			this.step = this.normalizeStep(this.step + 1);
		},
		previousStep() {
			this.step = this.normalizeStep(this.step - 1);
		},
		isActive(value) {
			return this.step === value;
		},
		isComplete(value) {
			return this.step > value;
		},
		get canAdvance() {
			if (this.step === 1) {
				return this.requirementsPass;
			}

			return this.step < this.maxStep;
		},
		get progressWidth() {
			return `${((this.step - 1) / (this.maxStep - 1)) * 100}%`;
		},
		async testDatabaseConnection(form) {
			if (!this.databaseTestEndpoint || !form) {
				return;
			}

			this.databaseStatus = {
				state: 'testing',
				message: 'Testing the database connection...',
			};

			try {
				const rawEndpoint = `${this.databaseTestEndpoint || ''}`.trim();
				const endpoint = new URL(rawEndpoint || '/install/test-database', window.location.origin);
				const requestUrl = endpoint.origin === window.location.origin
					? endpoint.toString()
					: `${window.location.origin}${endpoint.pathname}${endpoint.search}`;

				const formData = new FormData();
				const csrfToken = form.querySelector('[name="_token"]')?.value
					?? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
					?? '';

				if (csrfToken) {
					formData.append('_token', csrfToken);
				}

				['db_host', 'db_port', 'db_database', 'db_username', 'db_password'].forEach((field) => {
					formData.append(field, form.querySelector(`[name="${field}"]`)?.value ?? '');
				});

				const response = await fetch(requestUrl, {
					method: 'POST',
					body: formData,
					headers: {
						Accept: 'application/json',
						...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
						'X-Requested-With': 'XMLHttpRequest',
					},
					credentials: 'same-origin',
				});

				const payload = await response.json().catch(() => ({}));

				if (!response.ok) {
					this.databaseStatus = {
						state: 'failed',
						message: payload.message || 'The installer could not connect to the database with the provided credentials.',
					};
					return;
				}

				this.databaseStatus = {
					state: 'passed',
					message: payload.message || 'Database connection successful.',
				};
			} catch (error) {
				this.databaseStatus = {
					state: 'failed',
					message: error.message || 'The database test could not be completed.',
				};
			}
		},
	}));

	Alpine.data('themeManager', (config) => ({
		defaultMode: config.mode ?? 'dark',
		defaultPreset: config.preset ?? 'cleopatra',
		allowPresetPreview: config.allowPresetPreview ?? false,
		initialToasts: config.initialToasts ?? [],
		searchIndexUrl: config.searchIndexUrl ?? '/admin/search',
		mode: config.mode ?? 'dark',
		preset: config.preset ?? 'cleopatra',
		authAttention: 'idle',
		activePasswordField: null,
		passwordStrength: {
			score: 0,
			label: 'Start typing',
			hint: 'Use 8 or more characters with a mix of letters, numbers, and symbols.',
			tone: 'idle',
		},
		visiblePasswords: {},
		searchQuery: config.initialSearchQuery ?? '',
		mobileNavOpen: false,
		mobileSearchOpen: false,
		modalOpen: false,
		modalBusy: false,
		sidebarCollapsed: false,
		uiLoading: false,
		loadingMessage: 'Loading workspace...',
		toasts: [],
		searchTimeout: null,
		get isDark() {
			return window.resolveDarkState(this.mode);
		},
		get displayMode() {
			return (this.mode || this.defaultMode || 'dark').toUpperCase();
		},
		get displayPreset() {
			return (this.preset || this.defaultPreset || 'cleopatra').toUpperCase();
		},
		get anyPasswordVisible() {
			return Object.values(this.visiblePasswords).some(Boolean);
		},
		get authEyeOffsetX() {
			if (this.authAttention !== 'password') {
				return 0;
			}

			return this.activePasswordField === 'register-password-confirmation' ? 16 : 11;
		},
		get authEyeOffsetY() {
			if (this.authAttention !== 'password') {
				return 0;
			}

			return this.activePasswordField === 'register-password-confirmation' ? 12 : (this.anyPasswordVisible ? 6 : 10);
		},
		get authEyeScale() {
			if (this.anyPasswordVisible) {
				return 1.18;
			}

			return this.authAttention === 'password' ? 1.06 : 1;
		},
		get strengthMood() {
			if (this.passwordStrength.score >= 4) {
				return 'strong';
			}

			if (this.passwordStrength.score === 3) {
				return 'good';
			}

			if (this.passwordStrength.score >= 1) {
				return 'weak';
			}

			return 'idle';
		},
		previewSignature() {
			return `${this.defaultMode}:${this.defaultPreset}`;
		},
		syncPreviewState() {
			const storedSignature = localStorage.getItem(window.themePreviewStorageKey);

			if (storedSignature !== this.previewSignature()) {
				localStorage.removeItem('theme-preview-mode');
				localStorage.removeItem('theme-preview-preset');
				localStorage.setItem(window.themePreviewStorageKey, this.previewSignature());
			}

			this.mode = localStorage.getItem('theme-preview-mode') ?? this.defaultMode;

			if (!this.allowPresetPreview) {
				localStorage.removeItem('theme-preview-preset');
			}

			this.preset = this.allowPresetPreview
				? (localStorage.getItem('theme-preview-preset') ?? this.defaultPreset)
				: this.defaultPreset;
		},
		init() {
			this.syncPreviewState();
			this.sidebarCollapsed = localStorage.getItem(window.sidebarCollapseStorageKey) === '1';
			window.applyThemePreference({ mode: this.mode, preset: this.preset });
			this.initialToasts.forEach((toast) => this.queueToast(toast.type, toast.message));
			this.registerUiListeners();

			window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
				if (this.mode === 'system') {
					window.applyThemePreference({ mode: this.mode, preset: this.preset });
				}
			});

			window.addEventListener('resize', () => {
				if (window.innerWidth >= 1024) {
					this.closeMobileNav();
				}
			});
		},
		registerUiListeners() {
			window.addEventListener('app-toast', (event) => {
				this.queueToast(event.detail.type, event.detail.message);
			});

			window.addEventListener('app-loading-start', (event) => {
				this.startLoading(event.detail?.message);
			});

			window.addEventListener('app-loading-stop', () => {
				this.stopLoading();
			});

			window.addEventListener('pageshow', () => {
				this.stopLoading();
			});

			document.addEventListener('submit', async (event) => {
				const form = event.target;

				if (!(form instanceof HTMLFormElement) || form.dataset.skipLoading === 'true') {
					return;
				}

				if (form.dataset.modalForm === 'true') {
					event.preventDefault();
					await this.submitModalForm(form);
					return;
				}

				if (form.dataset.asyncForm === 'true') {
					event.preventDefault();
					await this.submitAsyncForm(form);
					return;
				}

				if (form.dataset.confirmDelete === 'true' && form.dataset.confirmed !== 'true') {
					event.preventDefault();

					const confirmed = await window.confirmDestructiveAction({
						title: form.dataset.confirmTitle || 'Delete this record?',
						text: form.dataset.confirmText || 'This action cannot be undone.',
						confirmButtonText: form.dataset.confirmButton || 'Delete',
						cancelButtonText: form.dataset.cancelButton || 'Cancel',
					});

					if (!confirmed) {
						return;
					}

					form.dataset.confirmed = 'true';

					if (event.submitter instanceof HTMLElement) {
						form.requestSubmit(event.submitter);
					} else {
						form.requestSubmit();
					}
					return;
				}

				delete form.dataset.confirmed;

				const method = (form.getAttribute('method') || 'GET').toUpperCase();

				if (method !== 'GET' || form.dataset.loading !== undefined) {
					this.startLoading(form.dataset.loadingMessage || 'Working...');
				}
			});

			document.addEventListener('click', (event) => {
				const modalTrigger = event.target.closest('[data-modal-url]');

				if (modalTrigger instanceof HTMLElement) {
					event.preventDefault();
					this.openCrudModal(modalTrigger.dataset.modalUrl);
					return;
				}

				const modalCloseTrigger = event.target.closest('[data-modal-close]');

				if (modalCloseTrigger instanceof HTMLElement) {
					event.preventDefault();
					this.closeCrudModal();
					return;
				}

				const downloadTrigger = event.target.closest('a[data-download]');

				if (downloadTrigger instanceof HTMLAnchorElement) {
					event.preventDefault();

					window.downloadFile({
						url: downloadTrigger.href,
						filename: downloadTrigger.dataset.downloadFilename || 'download',
						message: downloadTrigger.dataset.loadingMessage || 'Preparing download...',
					}).catch((error) => {
						window.dispatchAppToast('error', error.message);
					});

					return;
				}

				const trigger = event.target.closest('a[data-loading], button[data-loading]');

				if (!(trigger instanceof HTMLElement)) {
					return;
				}

				if (trigger.dataset.download !== undefined || trigger.dataset.exportUrl) {
					return;
				}

				if (trigger.tagName === 'A' && trigger.getAttribute('href')?.startsWith('#')) {
					return;
				}

				this.startLoading(trigger.dataset.loadingMessage || 'Opening...');
			});

			window.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && this.modalOpen && !this.modalBusy) {
					this.closeCrudModal();
				}
			});
		},
		updateBodyScrollLock() {
			document.body.classList.toggle('overflow-hidden', this.mobileNavOpen || this.modalOpen);
		},
		startLoading(message = 'Loading workspace...') {
			this.loadingMessage = message;
			this.uiLoading = true;
		},
		stopLoading() {
			this.uiLoading = false;
		},
		queueToast(type, message) {
			if (!message) {
				return;
			}

			const id = `${Date.now()}-${Math.random()}`;
			const toast = { id, type, message, visible: true };
			this.toasts.push(toast);

			window.setTimeout(() => this.dismissToast(id), 4200);
		},
		dismissToast(id) {
			this.toasts = this.toasts.filter((toast) => toast.id !== id);
		},
		toggleMobileNav() {
			this.mobileNavOpen = !this.mobileNavOpen;
			this.updateBodyScrollLock();
		},
		closeMobileNav() {
			this.mobileNavOpen = false;
			this.updateBodyScrollLock();
		},
		toggleMobileSearch() {
			this.mobileSearchOpen = !this.mobileSearchOpen;

			if (this.mobileSearchOpen) {
				this.$nextTick(() => this.$refs.mobileSearchInput?.focus());
			}
		},
		handleSearchInput() {
			window.clearTimeout(this.searchTimeout);

			const query = this.searchQuery.trim();

			if (query.length < 2) {
				return;
			}

			this.searchTimeout = window.setTimeout(() => {
				this.submitSearch();
			}, 350);
		},
		submitSearch() {
			const query = this.searchQuery.trim();
			const url = new URL(this.searchIndexUrl, window.location.origin);

			if (query) {
				url.searchParams.set('query', query);
			}

			window.location.assign(url.toString());
		},
		toggleSidebar() {
			this.sidebarCollapsed = !this.sidebarCollapsed;
			localStorage.setItem(window.sidebarCollapseStorageKey, this.sidebarCollapsed ? '1' : '0');
		},
		toggleMode() {
			this.mode = this.isDark ? 'light' : 'dark';
			localStorage.setItem('theme-preview-mode', this.mode);
			localStorage.setItem(window.themePreviewStorageKey, this.previewSignature());
			window.applyThemePreference({ mode: this.mode, preset: this.preset });
		},
		focusPasswordField(field) {
			this.activePasswordField = field;
			this.authAttention = 'password';
		},
		blurPasswordField(field) {
			if (this.activePasswordField === field) {
				this.activePasswordField = null;
			}

			this.authAttention = this.anyPasswordVisible ? 'password' : 'idle';
		},
		isPasswordVisible(field) {
			return Boolean(this.visiblePasswords[field]);
		},
		evaluatePasswordStrength(value) {
			return window.evaluatePasswordStrength(value);
		},
		updatePasswordStrength(value, field = null) {
			this.passwordStrength = this.evaluatePasswordStrength(value);

			if (field) {
				this.activePasswordField = field;
			}

			if (value?.length) {
				this.authAttention = 'password';
			}
		},
		togglePasswordVisibility(field) {
			this.visiblePasswords = {
				...this.visiblePasswords,
				[field]: !this.isPasswordVisible(field),
			};

			this.activePasswordField = field;
			this.authAttention = 'password';
		},
		setPreviewPreset(preset) {
			this.preset = preset;
			localStorage.setItem('theme-preview-preset', preset);
			localStorage.setItem(window.themePreviewStorageKey, this.previewSignature());
			window.applyThemePreference({ mode: this.mode, preset: this.preset });
		},
		resetToDefault() {
			this.mode = this.defaultMode;
			this.preset = this.defaultPreset;
			localStorage.removeItem('theme-preview-mode');
			localStorage.removeItem('theme-preview-preset');
			localStorage.setItem(window.themePreviewStorageKey, this.previewSignature());
			window.applyThemePreference({ mode: this.mode, preset: this.preset });
		},
		buildModalUrl(url) {
			const modalUrl = new URL(url, window.location.origin);
			modalUrl.searchParams.set('modal', '1');
			return modalUrl.toString();
		},
		async openCrudModal(url) {
			if (!url || this.modalBusy) {
				return;
			}

			this.modalOpen = true;
			this.modalBusy = true;
			this.updateBodyScrollLock();

			if (this.$refs.modalContent) {
				this.$refs.modalContent.innerHTML = '<div class="crud-modal-state"><span class="loader-spinner"></span><p class="text-sm text-muted">Loading form...</p></div>';
			}

			try {
				const response = await fetch(this.buildModalUrl(url), {
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
					},
					credentials: 'same-origin',
				});

				if (!response.ok) {
					throw new Error('Unable to open the form right now.');
				}

				const html = await response.text();
				this.$refs.modalContent.innerHTML = html;
				Alpine.initTree(this.$refs.modalContent);
			} catch (error) {
				this.closeCrudModal();
				window.dispatchAppToast('error', error.message);
			} finally {
				this.modalBusy = false;
			}
		},
		closeCrudModal() {
			this.modalOpen = false;
			this.modalBusy = false;
			if (this.$refs.modalContent) {
				this.$refs.modalContent.innerHTML = '';
			}
			this.updateBodyScrollLock();
		},
		clearModalErrors(form) {
			form.querySelectorAll('[data-field-error]').forEach((node) => {
				node.textContent = '';
				node.classList.add('hidden');
			});

			form.querySelectorAll('[data-field-invalid]').forEach((node) => {
				node.classList.remove('is-invalid');
				node.removeAttribute('data-field-invalid');
			});
		},
		applyModalErrors(form, errors = {}) {
			Object.entries(errors).forEach(([field, messages]) => {
				const baseField = field.replace(/\.\d+$/, '').replace(/\[\]$/, '');
				const errorNode = form.querySelector(`[data-field-error="${baseField}"]`);

				if (errorNode) {
					errorNode.textContent = messages[0] ?? 'Please review this field.';
					errorNode.classList.remove('hidden');
				}

				Array.from(form.elements)
					.filter((element) => element instanceof HTMLElement && (element.name || '').replace(/\[\]$/, '') === baseField)
					.forEach((element) => element.setAttribute('data-field-invalid', 'true'));
			});

			form.querySelectorAll('[data-field-invalid="true"]').forEach((node) => {
				node.classList.add('is-invalid');
			});
		},
		async submitAsyncForm(form) {
			if (form.dataset.submitting === 'true') {
				return;
			}

			form.dataset.submitting = 'true';
			this.clearModalErrors(form);
			this.startLoading(form.dataset.loadingMessage || 'Saving...');

			try {
				const response = await fetch(form.action, {
					method: 'POST',
					body: new FormData(form),
					headers: {
						Accept: 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
					},
					credentials: 'same-origin',
				});

				if (response.status === 422) {
					const payload = await response.json();
					this.applyModalErrors(form, payload.errors ?? {});
					const firstMessage = Object.values(payload.errors ?? {}).flat()[0] ?? 'Please review the form.';
					window.dispatchAppToast('error', firstMessage);
					return;
				}

				if (!response.ok) {
					throw new Error('Unable to save your changes right now.');
				}

				const payload = await response.json().catch(() => ({}));
				window.dispatchAppToast('success', payload.message || 'Saved successfully.');
			} catch (error) {
				window.dispatchAppToast('error', error.message);
			} finally {
				delete form.dataset.submitting;
				this.stopLoading();
			}
		},
		async submitModalForm(form) {
			if (this.modalBusy) {
				return;
			}

			this.modalBusy = true;
			this.clearModalErrors(form);

			try {
				const response = await fetch(form.action, {
					method: 'POST',
					body: new FormData(form),
					headers: {
						Accept: 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
					},
					credentials: 'same-origin',
				});

				if (response.status === 422) {
					const payload = await response.json();
					this.applyModalErrors(form, payload.errors ?? {});
					window.dispatchAppToast('error', 'Please correct the highlighted fields.');
					return;
				}

				if (!response.ok) {
					throw new Error('Unable to save your changes right now.');
				}

				const payload = await response.json();
				this.closeCrudModal();
				window.dispatchAppToast('success', payload.message || 'Saved successfully.');
				window.location.reload();
			} catch (error) {
				window.dispatchAppToast('error', error.message);
			} finally {
				this.modalBusy = false;
			}
		},
	}));

	Alpine.data('themeSettings', (preset = 'cleopatra', mode = 'dark') => ({
		selectedPreset: preset ?? 'cleopatra',
		selectedMode: mode ?? 'dark',
		selectPreset(preset) {
			this.selectedPreset = preset;
			window.applyThemePreference({ mode: this.selectedMode, preset: this.selectedPreset });
		},
		selectMode(mode) {
			this.selectedMode = mode;
			window.applyThemePreference({ mode: this.selectedMode, preset: this.selectedPreset });
		},
	}));

	Alpine.data('templateEditor', (templates = {}, selected = '', initialFields = null) => ({
		templates,
		selectedSlug: selected,
		initialFields,
		fields: {},
		editors: {},
		init() {
			this.loadTemplate(this.selectedSlug, this.initialFields);
		},
		loadTemplate(slug, initialFields = null) {
			const template = this.templates[slug] ?? Object.values(this.templates)[0] ?? { content: {} };
			this.selectedSlug = slug || Object.keys(this.templates)[0] || '';
			this.fields = { ...(template.content ?? {}), ...(initialFields ?? {}) };
			this.initialFields = null;
			this.syncEditors();
		},
		selectTemplate(slug) {
			this.loadTemplate(slug);
		},
		async initRichText(element, key) {
			if (!element || this.editors[key]) {
				return;
			}

			try {
				const ClassicEditor = await window.loadClassicEditor();
				const editor = await ClassicEditor.create(element, {
					toolbar: [
						'heading',
						'|',
						'bold',
						'italic',
						'link',
						'bulletedList',
						'numberedList',
						'blockQuote',
						'|',
						'undo',
						'redo',
					],
				});

				editor.setData(this.field(key, ''));
				element.value = this.field(key, '');

				editor.model.document.on('change:data', () => {
					const data = editor.getData();
					this.fields = { ...this.fields, [key]: data };
					element.value = data;
				});

				this.editors[key] = editor;
			} catch (error) {
				console.error('Unable to initialize rich text editor.', error);
			}
		},
		syncEditors() {
			Object.entries(this.editors).forEach(([key, editor]) => {
				const value = this.field(key, '');

				if (editor.getData() !== value) {
					editor.setData(value);
				}

				if (editor.sourceElement) {
					editor.sourceElement.value = value;
				}
			});
		},
		field(key, fallback = '') {
			return this.fields[key] ?? fallback;
		},
	}));

	Alpine.data('pageControls', (defaultOpen = true) => ({
		showControls: defaultOpen,
		toggleControls() {
			this.showControls = !this.showControls;
		},
	}));

	Alpine.data('userForm', (config = {}) => ({
		existingImage: config.existingImage ?? null,
		previewImage: config.existingImage ?? null,
		password: '',
		passwordConfirmation: '',
		passwordStrength: window.evaluatePasswordStrength(''),
		init() {
			this.passwordStrength = window.evaluatePasswordStrength(this.password);
		},
		get strengthWidth() {
			return `${Math.max(this.passwordStrength.score, this.password ? 1 : 0) * 25}%`;
		},
		get passwordsMatch() {
			if (!this.password && !this.passwordConfirmation) {
				return null;
			}

			return this.password === this.passwordConfirmation;
		},
		get matchLabel() {
			if (this.passwordsMatch === null) {
				return 'Waiting for confirmation';
			}

			return this.passwordsMatch ? 'Passwords match' : 'Passwords do not match';
		},
		updatePassword(value) {
			this.password = value;
			this.passwordStrength = window.evaluatePasswordStrength(value);
		},
		updatePasswordConfirmation(value) {
			this.passwordConfirmation = value;
		},
		previewSelectedImage(event) {
			const [file] = event.target.files ?? [];

			if (!file) {
				this.previewImage = this.existingImage;
				return;
			}

			const reader = new FileReader();
			reader.onload = () => {
				this.previewImage = reader.result;
			};
			reader.readAsDataURL(file);
		},
	}));

	Alpine.data('rolePicker', (selected = [], roles = [], lockedRoles = []) => ({
		query: '',
		selected,
		roles,
		lockedRoles,
		showSelectedOnly: false,
		get totalSelectedCount() {
			return this.selected.length + this.lockedRoles.length;
		},
		get filteredRoles() {
			return this.roles.filter((role) => {
				const matchesQuery = !this.query || role.name.toLowerCase().includes(this.query.toLowerCase());
				const matchesSelection = !this.showSelectedOnly || this.selected.includes(role.name);
				return matchesQuery && matchesSelection;
			});
		},
		get selectedRoles() {
			return this.roles.filter((role) => this.selected.includes(role.name));
		},
		matches(name) {
			return name.toLowerCase().includes(this.query.toLowerCase());
		},
		isSelected(name) {
			return this.selected.includes(name);
		},
		toggleRole(name) {
			if (this.lockedRoles.includes(name)) {
				return;
			}

			if (this.isSelected(name)) {
				this.selected = this.selected.filter((selectedName) => selectedName !== name);
				return;
			}

			this.selected = [...this.selected, name];
		},
		clearRoles() {
			this.selected = [];
		},
	}));

	Alpine.data('permissionMatrix', (selected = [], groups = {}) => ({
		query: '',
		selected,
		groups,
		matches(name) {
			return name.toLowerCase().includes(this.query.toLowerCase());
		},
		moduleMatches(permissions) {
			return permissions.some((permission) => this.matches(permission));
		},
		allSelected(permissions) {
			return permissions.every((permission) => this.selected.includes(permission));
		},
		toggleGroup(permissions) {
			if (this.allSelected(permissions)) {
				this.selected = this.selected.filter((permission) => !permissions.includes(permission));
				return;
			}

			this.selected = [...new Set([...this.selected, ...permissions])];
		},
		toggleAll() {
			const allPermissions = Object.values(this.groups).flat();

			if (allPermissions.every((permission) => this.selected.includes(permission))) {
				this.selected = [];
				return;
			}

			this.selected = [...new Set(allPermissions)];
		},
	}));

	Alpine.data('dashboardStudioEditor', (config = {}) => ({
		stats: config.stats ?? [],
		charts: config.charts ?? [],
		icons: config.icons ?? {},
		statSources: config.statSources ?? {},
		chartSources: config.chartSources ?? {},
		chartTypes: config.chartTypes ?? {},
		authVisuals: config.authVisuals ?? {},
		loginPreview: config.authVisuals?.login_image_url ?? null,
		registerPreview: config.authVisuals?.register_image_url ?? null,
		init() {
			this.syncHiddenFields();
			this.$watch('stats', () => this.syncHiddenFields());
			this.$watch('charts', () => this.syncHiddenFields());
		},
		syncHiddenFields() {
			if (this.$refs.statsInput) {
				this.$refs.statsInput.value = JSON.stringify(this.stats);
			}

			if (this.$refs.chartsInput) {
				this.$refs.chartsInput.value = JSON.stringify(this.charts);
			}
		},
		addStat() {
			const [sourceKey, sourceMeta] = Object.entries(this.statSources)[0] ?? ['total_users', { label: 'Stat', description: '' }];
			const [iconKey] = Object.keys(this.icons);

			this.stats = [
				...this.stats,
				{
					id: `stat-${Date.now()}`,
					label: sourceMeta.label,
					description: sourceMeta.description,
					icon: iconKey,
					source: sourceKey,
				},
			];
		},
		removeStat(index) {
			this.stats = this.stats.filter((item, itemIndex) => itemIndex !== index);
		},
		moveStat(index, direction) {
			const target = index + direction;

			if (target < 0 || target >= this.stats.length) {
				return;
			}

			const next = [...this.stats];
			const [item] = next.splice(index, 1);
			next.splice(target, 0, item);
			this.stats = next;
		},
		addChart() {
			const [sourceKey, sourceMeta] = Object.entries(this.chartSources)[0] ?? ['role_distribution', { default_title: 'Chart', description: '', default_type: 'bar', audience: 'all' }];

			this.charts = [
				...this.charts,
				{
					id: `chart-${Date.now()}`,
					title: sourceMeta.default_title ?? sourceMeta.label,
					description: sourceMeta.description,
					source: sourceKey,
					type: sourceMeta.default_type ?? 'bar',
					audience: sourceMeta.audience ?? 'all',
				},
			];
		},
		removeChart(index) {
			this.charts = this.charts.filter((item, itemIndex) => itemIndex !== index);
		},
		moveChart(index, direction) {
			const target = index + direction;

			if (target < 0 || target >= this.charts.length) {
				return;
			}

			const next = [...this.charts];
			const [item] = next.splice(index, 1);
			next.splice(target, 0, item);
			this.charts = next;
		},
		chartTypeChoices(sourceKey) {
			const source = this.chartSources[sourceKey] ?? {};
			return source.types ?? ['bar'];
		},
		normalizeChart(index) {
			const chart = this.charts[index];
			const choices = this.chartTypeChoices(chart.source);

			if (!choices.includes(chart.type)) {
				this.charts[index].type = choices[0];
			}

			if (!['all', 'super-admin'].includes(chart.audience)) {
				this.charts[index].audience = this.chartSources[chart.source]?.audience ?? 'all';
			}

			this.charts = [...this.charts];
		},
		previewImage(event, target) {
			const [file] = event.target.files ?? [];

			if (!file) {
				return;
			}

			const reader = new FileReader();
			reader.onload = () => {
				if (target === 'login') {
					this.loginPreview = reader.result;
					return;
				}

				this.registerPreview = reader.result;
			};
			reader.readAsDataURL(file);
		},
	}));

	Alpine.data('dashboardWidgets', (config) => ({
		widgets: config.widgets ?? {},
		layout: config.layout ?? [],
		available: config.available ?? [],
		dragEnabled: config.dragEnabled ?? false,
		canManageDrag: config.canManageDrag ?? false,
		endpoint: config.endpoint,
		csrfToken: config.csrfToken,
		showControls: config.controlsOpen ?? true,
		ready: false,
		bootDelay: config.bootDelay ?? 420,
		saving: false,
		draggingKey: null,
		init() {
			this.syncLayout();
			window.setTimeout(() => {
				this.ready = true;
				this.$nextTick(() => this.applyLayout());
			}, this.bootDelay);
		},
		get dragAllowed() {
			return this.canManageDrag && this.dragEnabled;
		},
		initCanvas() {
			this.$nextTick(() => this.applyLayout());
		},
		syncLayout() {
			const known = this.available.length ? this.available : Object.keys(this.widgets);
			const seeded = this.layout.filter((key) => known.includes(key));
			this.layout = [...new Set([...seeded, ...known.filter((key) => !seeded.includes(key))])];
		},
		isVisible(key) {
			return this.widgets[key] !== false;
		},
		toggleControls() {
			this.showControls = !this.showControls;
		},
		startDrag(key) {
			if (!this.dragAllowed) {
				return;
			}

			this.draggingKey = key;
		},
		endDrag() {
			this.draggingKey = null;
		},
		async dropWidget(targetKey) {
			if (!this.dragAllowed) {
				return;
			}

			if (!this.draggingKey || this.draggingKey === targetKey) {
				this.draggingKey = null;
				return;
			}

			const next = this.layout.filter((key) => key !== this.draggingKey);
			const targetIndex = next.indexOf(targetKey);
			next.splice(targetIndex < 0 ? next.length : targetIndex, 0, this.draggingKey);
			this.layout = next;
			this.draggingKey = null;
			this.applyLayout();
			await this.persist();
		},
		applyLayout() {
			const canvas = this.$refs.canvas;

			if (!canvas) {
				return;
			}

			const widgets = new Map(
				Array.from(canvas.querySelectorAll('[data-widget-key]')).map((node) => [node.dataset.widgetKey, node]),
			);

			this.layout.forEach((key) => {
				const node = widgets.get(key);

				if (node) {
					canvas.appendChild(node);
				}
			});
		},
		async toggleWidget(key) {
			this.widgets[key] = !this.isVisible(key);
			this.syncLayout();
			this.applyLayout();
			await this.persist();
		},
		async toggleDrag() {
			if (!this.canManageDrag) {
				return;
			}

			this.dragEnabled = !this.dragEnabled;
			this.draggingKey = null;
			await this.persist();
		},
		async persist() {
			if (this.saving) {
				return;
			}

			this.saving = true;

			try {
				const response = await fetch(this.endpoint, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': this.csrfToken,
						Accept: 'application/json',
					},
						body: JSON.stringify({ widgets: this.widgets, layout: this.layout, drag_enabled: this.dragEnabled }),
				});

				if (!response.ok) {
					throw new Error('Unable to save widget preferences.');
				}

					const payload = await response.json().catch(() => ({}));
					window.dispatchAppToast('success', payload.message || 'Dashboard widgets updated.');
			} catch (error) {
				window.dispatchAppToast('error', error.message);
			} finally {
				this.saving = false;
			}
		},
	}));
});

Alpine.start();
