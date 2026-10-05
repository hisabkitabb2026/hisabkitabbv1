const { Fragment: e, computed: t, createBlock: n, createCommentVNode: r, createElementBlock: i, createElementVNode: a, createTextVNode: o, createVNode: s, defineComponent: c, getCurrentInstance: l, h: u, normalizeClass: d, normalizeStyle: f, onBeforeUnmount: p, onMounted: m, onUnmounted: h, openBlock: g, ref: _, renderList: v, resolveComponent: y, toDisplayString: b, unref: x, vModelSelect: S, watch: C, withCtx: w, withDirectives: T, withModifiers: E } = window.__invoiceshelf_vue;
//#region resources/js/messages.ts
var D = { en: { trips: {
	title: "Trips",
	new_trip: "New Trip",
	search_placeholder: "Search route, lorry, goods...",
	filters: "Filters",
	customer: "Customer",
	all_customers: "All customers",
	status: "Status",
	all_statuses: "All statuses",
	unbilled_only: "Unbilled only",
	board: "Board",
	list: "List",
	empty_title: "No trips yet",
	empty_description: "Your board is empty. Create your first trip by mapping an existing LR Receipt, or by filling a new one.",
	map_existing: "Map existing LRs",
	create_new: "Create new LR",
	unlinked_lrs: "Unlinked LR Receipts",
	no_unlinked: "No unlinked LR receipts. Create one first, or use the Create tab.",
	selected: "Selected: {count} LR(s) · {amount}",
	mixed_routes: "Different routes selected — same vehicle?",
	create_trip: "Create Trip",
	create_trip_count: "Create Trip ({count})",
	customer_field: "Customer",
	from: "From",
	to: "To",
	goods: "Goods",
	weight: "Weight",
	pickup_date: "Pickup date",
	basic_freight: "Basic freight",
	hamali: "Hamali",
	door_delivery: "Door delivery",
	other_charge: "Other charge",
	net_amount: "Net amount",
	creating: "Creating...",
	trip_no: "Trip",
	cancelled: "Cancelled",
	open: "Open trip",
	cancel_trip: "Cancel trip",
	cancel_confirm: "Cancel this trip? It stays on the board, greyed out, and can never be billed.",
	move_to: "Move to",
	trip_detail: {
		documents: "Documents",
		money: "The Money",
		lr_receipts: "LR Receipts",
		no_lr_receipts: "No LR receipts linked yet.",
		map_another: "Map another LR",
		lorry_receipt: "Lorry Receipt",
		map_lorry: "Map existing",
		create_lorry: "Create in Lorry Receipts",
		view: "View",
		unlink: "Unlink",
		revenue: "Revenue",
		cost: "Cost",
		profit: "Profit",
		owner_paid: "Owner paid",
		owner_balance: "Owner balance",
		record_payment: "Record owner payment",
		amount: "Amount",
		method: "Method",
		note: "Note",
		date: "Date",
		cancel_trip: "Cancel Trip",
		cancelled: "This trip is cancelled."
	},
	load_failed: "Unable to load the board.",
	trip_created: "Trip #{number} created.",
	trip_cancelled: "Trip cancelled.",
	move_failed: "Unable to move the trip.",
	save_failed: "Unable to save the trip.",
	create_failed: "Unable to create the trip.",
	link_failed: "Unable to link the receipt.",
	unbilled_amount: "Unbilled amount",
	trips_this_month: "trips"
} } }, O = "trips", k = `/admin/modules/${O}`, A = {
	board: k,
	list: `${k}/list`,
	week: `${k}/week`,
	new: `${k}/new`,
	trip: (e) => `${k}/${e}`,
	reports: `${k}/reports`,
	invoiceView: (e) => `/admin/invoices/${e}/view`,
	invoiceCreate: (e) => `/admin/invoices/create?template=${e}`
}, j = {
	trips: `extension.page.${O}.trips`,
	board: `extension.page.${O}.trips.board`,
	list: `extension.page.${O}.trips.list`,
	week: `extension.page.${O}.trips.week`,
	trip: `extension.page.${O}.trip`,
	reports: `extension.page.${O}.reports`
};
function M(e, t) {
	return c({ setup: (n, { attrs: r }) => () => u(t, {
		...r,
		client: e.client,
		notify: (t, n) => {
			e.notify(t, n);
		},
		router: e.router
	}) });
}
//#endregion
//#region resources/js/support/i18n.ts
function ee() {
	return l()?.appContext.config.globalProperties.$t ?? ((e) => e);
}
//#endregion
//#region resources/js/support/useTripReportDownload.ts
function te(e, t) {
	async function n() {
		let n = t();
		if (n) try {
			let t = await e.get(n + "&download=true", { responseType: "blob" }), r = URL.createObjectURL(t.data), i = document.createElement("a");
			i.href = r, i.download = "trip-report.pdf", document.body.appendChild(i), i.click(), document.body.removeChild(i), setTimeout(() => URL.revokeObjectURL(r), 5e3);
		} catch {}
	}
	return () => {
		n();
	};
}
//#endregion
//#region resources/js/support/date-range.ts
var N = (e) => e.getFullYear() + "-" + String(e.getMonth() + 1).padStart(2, "0") + "-" + String(e.getDate()).padStart(2, "0");
function ne(e) {
	let t = new Date(e);
	return t.setHours(0, 0, 0, 0), t;
}
function re(e) {
	let t = new Date(e);
	return t.setHours(23, 59, 59, 999), t;
}
function ie(e) {
	let t = ne(e), n = (t.getDay() + 6) % 7;
	return t.setDate(t.getDate() - n), t;
}
function ae(e) {
	let t = ie(e), n = new Date(t);
	return n.setDate(t.getDate() + 6), re(n);
}
function oe(e) {
	return ne(new Date(e.getFullYear(), e.getMonth(), 1));
}
function se(e) {
	return re(new Date(e.getFullYear(), e.getMonth() + 1, 0));
}
function ce(e) {
	let t = Math.floor(e.getMonth() / 3) * 3;
	return ne(new Date(e.getFullYear(), t, 1));
}
function le(e) {
	let t = Math.floor(e.getMonth() / 3) * 3;
	return re(new Date(e.getFullYear(), t + 3, 0));
}
function ue(e) {
	return ne(new Date(e.getFullYear(), 0, 1));
}
function de(e) {
	return re(new Date(e.getFullYear(), 12, 0));
}
var fe = {
	isoWeek: ie,
	month: oe,
	quarter: ce,
	year: ue
}, pe = {
	isoWeek: ae,
	month: se,
	quarter: le,
	year: de
};
function me(e, t) {
	return {
		from: N(fe[e](t)),
		to: N(pe[e](t))
	};
}
function he(e, t) {
	let n = new Date(t);
	return e === "isoWeek" ? n.setDate(n.getDate() - 7) : e === "month" ? n.setMonth(n.getMonth() - 1) : e === "quarter" ? n.setMonth(n.getMonth() - 3) : n.setFullYear(n.getFullYear() - 1), {
		from: N(fe[e](n)),
		to: N(pe[e](n))
	};
}
function ge() {
	return me("month", /* @__PURE__ */ new Date());
}
function _e(e) {
	let t = /* @__PURE__ */ new Date();
	switch (e) {
		case "This Week": return me("isoWeek", t);
		case "This Month": return me("month", t);
		case "This Quarter": return me("quarter", t);
		case "This Year": return me("year", t);
		case "Previous Week": return he("isoWeek", t);
		case "Previous Month": return he("month", t);
		case "Previous Quarter": return he("quarter", t);
		case "Previous Year": return he("year", t);
		default: return {
			from: N(t),
			to: N(t)
		};
	}
}
//#endregion
//#region resources/js/support/period.ts
function ve(e) {
	return [
		["Today", "dateRange.today"],
		["This Week", "dateRange.this_week"],
		["This Month", "dateRange.this_month"],
		["This Quarter", "dateRange.this_quarter"],
		["This Year", "dateRange.this_year"],
		["Previous Week", "dateRange.previous_week"],
		["Previous Month", "dateRange.previous_month"],
		["Previous Quarter", "dateRange.previous_quarter"],
		["Previous Year", "dateRange.previous_year"]
	].map(([t, n]) => ({
		key: t,
		label: e(n),
		range: () => _e(t)
	}));
}
function ye(e) {
	let t = e.range?.();
	return {
		preset: e.key,
		from: t?.from ?? null,
		to: t?.to ?? null
	};
}
//#endregion
//#region resources/js/components/TripReportPdfPane.vue?vue&type=script&setup=true&lang.ts
var be = {
	key: 0,
	class: "flex items-center justify-center w-full h-screen border border-line-default border-solid rounded bg-surface"
}, xe = {
	key: 1,
	class: "flex flex-col items-center justify-center gap-4 w-full h-screen border border-line-default border-solid rounded bg-surface"
}, Se = ["src"], Ce = /* @__PURE__ */ c({
	__name: "TripReportPdfPane",
	props: {
		client: { type: [Function, Object] },
		path: {}
	},
	setup(e, { expose: t }) {
		let n = e, r = _("idle"), c = _(null), l = null, u = null, d = 0;
		function f() {
			c.value && URL.revokeObjectURL(c.value), c.value = null, l = null, u = null;
		}
		async function m(e) {
			let t = ++d;
			f(), r.value = "loading";
			try {
				let i = await n.client.get(e, { responseType: "blob" });
				if (t !== d) return;
				let a = i.data;
				if (a.type.startsWith("text/html") || a.type.startsWith("application/json")) throw Error("Server returned a message instead of a PDF");
				l = a, u = e, c.value = URL.createObjectURL(a), r.value = "ready";
			} catch {
				t === d && (r.value = "error");
			}
		}
		function h() {
			n.path && m(n.path);
		}
		async function v(e) {
			let t = e ?? n.path;
			if (t) {
				if (l && u === t) {
					let e = URL.createObjectURL(l);
					window.open(e, "_blank"), setTimeout(() => URL.revokeObjectURL(e), 3e4);
					return;
				}
				try {
					let e = await n.client.get(t, { responseType: "blob" }), r = URL.createObjectURL(e.data);
					window.open(r, "_blank"), setTimeout(() => URL.revokeObjectURL(r), 3e4);
				} catch {
					r.value = "error";
				}
			}
		}
		return C(() => n.path, (e) => {
			if (e) {
				m(e);
				return;
			}
			d++, f(), r.value = "idle";
		}, { immediate: !0 }), p(f), t({ view: v }), (e, t) => {
			let n = y("BaseSpinner"), l = y("BaseIcon"), u = y("BaseButton");
			return g(), i("div", null, [r.value === "loading" || r.value === "idle" ? (g(), i("div", be, [s(n, { class: "w-8 h-8 text-primary-400" })])) : r.value === "error" ? (g(), i("div", xe, [
				s(l, {
					name: "ExclamationCircleIcon",
					class: "w-12 h-12 text-muted"
				}),
				t[1] ||= a("p", { class: "text-sm text-muted" }, "Unable to load the report.", -1),
				s(u, {
					variant: "primary-outline",
					size: "sm",
					onClick: h
				}, {
					default: w(() => [...t[0] ||= [o("Retry", -1)]]),
					_: 1
				})
			])) : (g(), i("iframe", {
				key: 2,
				src: c.value ?? void 0,
				title: "Trip Report PDF",
				class: "w-full h-screen border border-line-default border-solid rounded bg-surface"
			}, null, 8, Se))]);
		};
	}
}), we = { class: "grid gap-8 md:grid-cols-12 pt-10" }, Te = { class: "col-span-8 md:col-span-4" }, Ee = { class: "hidden mt-6 md:block" }, De = { class: "col-span-8" }, Oe = /* @__PURE__ */ c({
	__name: "TripReportsPage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(e) {
		let r = e, i = ve(ee()), c = _(ye(i[2])), l = [
			{
				label: "By Customer Name",
				value: "customer"
			},
			{
				label: "By LR No",
				value: "lr"
			},
			{
				label: "By Invoice No",
				value: "invoice"
			},
			{
				label: "By Supplier Name",
				value: "supplier"
			}
		], u = _("customer"), f = _(null), p = _(null), h = ge(), v = _(h.from), b = _(h.to), S = _(""), T = _(""), D = _(""), O = _(""), k = t(() => f.value), j = t(() => {
			let e = "/api/v1/trips/reports/pdf?from_date=" + v.value + "&to_date=" + b.value + "&type=" + u.value;
			return S.value.trim() && (e += "&customer=" + encodeURIComponent(S.value.trim())), T.value.trim() && (e += "&lr_no=" + encodeURIComponent(T.value.trim())), D.value.trim() && (e += "&invoice_no=" + encodeURIComponent(D.value.trim())), O.value.trim() && (e += "&supplier=" + encodeURIComponent(O.value.trim())), e;
		}), M = te(r.client, () => (f.value = j.value, f.value));
		m(() => {
			f.value = j.value;
		}), C(c, (e) => {
			e.from && e.to && (v.value = e.from, b.value = e.to);
		}), C([
			v,
			b,
			u,
			S,
			T,
			D,
			O
		], () => {
			f.value = j.value;
		});
		function N() {
			return f.value = j.value, !0;
		}
		function ne() {
			N(), p.value?.view(f.value);
		}
		function re() {
			r.router.push(A.board);
		}
		return (t, r) => {
			let f = y("BaseBreadcrumbItem"), m = y("BaseBreadcrumb"), h = y("BaseIcon"), _ = y("BaseButton"), v = y("BasePageHeader"), b = y("BasePeriodPicker"), C = y("BaseInputGroup"), j = y("BaseMultiselect"), ee = y("BaseInput"), te = y("BasePage");
			return g(), n(te, null, {
				default: w(() => [s(v, { title: "Trip Reports" }, {
					actions: w(() => [s(_, {
						variant: "white",
						onClick: re
					}, {
						left: w((e) => [s(h, {
							name: "ArrowLeftIcon",
							class: d(e.class)
						}, null, 8, ["class"])]),
						default: w(() => [r[6] ||= o(" Back to Trips ", -1)]),
						_: 1
					}), s(_, {
						variant: "primary",
						class: "ms-4",
						onClick: x(M)
					}, {
						left: w((e) => [s(h, {
							name: "ArrowDownTrayIcon",
							class: d(e.class)
						}, null, 8, ["class"])]),
						default: w(() => [r[7] ||= o(" Download PDF ", -1)]),
						_: 1
					}, 8, ["onClick"])]),
					default: w(() => [s(m, null, {
						default: w(() => [
							s(f, {
								title: "Dashboard",
								to: "/admin/dashboard"
							}),
							s(f, {
								title: "Trips",
								to: x(A).board
							}, null, 8, ["to"]),
							s(f, {
								title: "Reports",
								to: "#",
								active: ""
							})
						]),
						_: 1
					})]),
					_: 1
				}), a("div", we, [a("div", Te, [
					s(C, {
						label: "Date Range",
						class: "mb-6"
					}, {
						default: w(() => [s(b, {
							modelValue: c.value,
							"onUpdate:modelValue": r[0] ||= (e) => c.value = e,
							presets: x(i),
							block: "",
							position: "bottom-start"
						}, null, 8, ["modelValue", "presets"])]),
						_: 1
					}),
					s(C, {
						label: "Report Type",
						class: "col-span-12 md:col-span-8"
					}, {
						default: w(() => [s(j, {
							modelValue: u.value,
							"onUpdate:modelValue": [r[1] ||= (e) => u.value = e, N],
							options: l,
							placeholder: "Select report type",
							class: "mt-1"
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					s(C, {
						label: "Customer Name",
						class: "col-span-12 md:col-span-8 mt-4"
					}, {
						default: w(() => [s(ee, {
							modelValue: S.value,
							"onUpdate:modelValue": [r[2] ||= (e) => S.value = e, N],
							placeholder: "Type customer name..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					s(C, {
						label: "LR No",
						class: "col-span-12 md:col-span-8"
					}, {
						default: w(() => [s(ee, {
							modelValue: T.value,
							"onUpdate:modelValue": [r[3] ||= (e) => T.value = e, N],
							placeholder: "Type LR number..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					s(C, {
						label: "Invoice No",
						class: "col-span-12 md:col-span-8"
					}, {
						default: w(() => [s(ee, {
							modelValue: D.value,
							"onUpdate:modelValue": [r[4] ||= (e) => D.value = e, N],
							placeholder: "Type invoice number..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					s(C, {
						label: "Supplier Name",
						class: "col-span-12 md:col-span-8"
					}, {
						default: w(() => [s(ee, {
							modelValue: O.value,
							"onUpdate:modelValue": [r[5] ||= (e) => O.value = e, N],
							placeholder: "Type supplier name..."
						}, null, 8, ["modelValue"])]),
						_: 1
					}),
					a("div", Ee, [s(_, {
						variant: "primary",
						class: "justify-center w-full",
						type: "submit",
						onClick: E(N, ["prevent"])
					}, {
						left: w((e) => [s(h, {
							name: "ArrowPathIcon",
							class: d(e.class)
						}, null, 8, ["class"])]),
						default: w(() => [r[8] ||= o(" Update Report ", -1)]),
						_: 1
					})])
				]), a("div", De, [s(Ce, {
					ref_key: "pdfPane",
					ref: p,
					client: e.client,
					path: k.value,
					class: "hidden md:block"
				}, null, 8, ["client", "path"]), a("button", {
					type: "button",
					class: "flex items-center justify-center w-full gap-2 px-5 text-sm font-medium transition-colors rounded-lg h-11 md:hidden bg-btn-primary text-on-primary hover:bg-btn-primary-hover",
					onClick: ne
				}, [s(h, {
					name: "DocumentTextIcon",
					class: "w-5 h-5"
				}), r[9] ||= o(" View PDF ", -1)])])])]),
				_: 1
			});
		};
	}
}), ke = `${O}:view-trip`;
function Ae(e) {
	e.registerPage({
		id: "reports",
		module: O,
		path: "reports",
		component: M(e, Oe),
		meta: {
			ability: ke,
			title: "Trip Reports"
		}
	});
}
//#endregion
//#region resources/js/api/trips.ts
var P = "/api/v1/trips";
async function je(e, t = {}) {
	let { data: n } = await e.get(P, { params: t });
	return n.data;
}
async function Me(e) {
	let { data: t } = await e.get(`${P}/statuses`);
	return t.data;
}
async function Ne(e, t) {
	let { data: n } = await e.get(`${P}/${t}`);
	return n.data;
}
async function Pe(e, t) {
	let { data: n } = await e.post(`${P}/from-lrs`, { invoice_ids: t });
	return n.data;
}
async function Fe(e, t, n, r) {
	let { data: i } = await e.post(`${P}/${t}/move`, {
		status_id: n,
		position: r
	});
	return i.data;
}
async function Ie(e, t) {
	let { data: n } = await e.post(`${P}/${t}/cancel`);
	return n.data;
}
async function Le(e, t, n) {
	let { data: r } = await e.patch(`${P}/${t}/status`, { status_id: n });
	return r.data;
}
async function Re(e, t = "lr", n = null) {
	let { data: r } = await e.get(`${P}/unlinked-lrs`, { params: {
		type: t,
		customer_id: n
	} });
	return r.data;
}
async function ze(e, t) {
	if (t.length === 0) return [];
	let { data: n } = await e.post(`${P}/lorry-match`, { invoice_ids: t });
	return n.data;
}
async function Be(e, t, n, r) {
	let { data: i } = await e.post(`${P}/${t}/receipts`, {
		invoice_id: n,
		type: r
	});
	return i.data;
}
async function Ve(e, t) {
	let { data: n } = await e.post(`${P}/${t}/fetch-invoice-receipt`);
	return n.data;
}
async function He(e, t, n, r) {
	let i = new FormData();
	i.append("file", n), i.append("category", r);
	let { data: a } = await e.post(`${P}/${t}/pod`, i, { headers: { "Content-Type": "multipart/form-data" } });
	return a.data;
}
async function Ue(e, t, n) {
	let { data: r } = await e.delete(`${P}/${t}/pod/${n}`);
	return r.data;
}
async function We(e, t, n) {
	let { data: r } = await e.get(`${P}/${t}/pod/${n}`, { responseType: "blob" });
	return r;
}
async function Ge(e, t, n) {
	let { data: r } = await e.delete(`${P}/${t}/receipts/${n}`);
	return r.data;
}
//#endregion
//#region resources/js/support/http.ts
function F(e, t) {
	if (typeof e == "object" && e) {
		let t = e.response?.data;
		if (typeof t == "object" && t) {
			let e = t.errors;
			if (typeof e == "object" && e) {
				let t = Object.values(e)[0];
				if (Array.isArray(t) && typeof t[0] == "string") return t[0];
			}
			let n = t.message;
			if (typeof n == "string" && n !== "") return n;
		}
	}
	return t;
}
//#endregion
//#region resources/js/support/format.ts
function I(e) {
	return ((e ?? 0) / 100).toLocaleString(void 0, {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2
	});
}
function Ke() {
	let e = /* @__PURE__ */ new Date();
	return `${e.getFullYear()}-${String(e.getMonth() + 1).padStart(2, "0")}-${String(e.getDate()).padStart(2, "0")}`;
}
//#endregion
//#region resources/js/pages/TripCreatePage.vue?vue&type=script&setup=true&lang.ts
var qe = { class: "mt-5 border-t border-line-light pt-5" }, Je = { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, Ye = { class: "flex gap-2" }, Xe = {
	key: 0,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, Ze = {
	key: 1,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, Qe = {
	key: 2,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, $e = {
	key: 3,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, et = {
	key: 4,
	class: "space-y-3"
}, tt = { class: "flex h-[38px] items-center rounded-lg bg-surface-tertiary px-3 text-sm font-medium text-heading" }, nt = { class: "justify-self-end" }, rt = {
	key: 5,
	class: "mt-3 text-sm text-alert-warning-text"
}, it = { class: "mt-4 flex flex-wrap justify-end gap-x-8 gap-y-2 border-t border-line-light pt-4 text-sm" }, at = { class: "text-muted" }, ot = { class: "font-semibold text-heading" }, st = { class: "text-muted" }, ct = { class: "font-semibold text-heading" }, lt = { class: "mt-5 border-t border-line-light pt-5" }, ut = {
	key: 0,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, dt = {
	key: 1,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, ft = {
	key: 2,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, pt = {
	key: 3,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted",
	role: "status"
}, mt = {
	key: 4,
	class: "flex items-center justify-between gap-3 rounded-lg border border-primary-200 bg-primary-50 p-3"
}, ht = { class: "min-w-0" }, gt = { class: "text-sm font-semibold text-heading" }, _t = { class: "mt-0.5 truncate text-xs text-muted" }, vt = { key: 0 }, yt = { class: "shrink-0 text-sm font-semibold text-heading" }, bt = { class: "mt-5 border-t border-line-light pt-5" }, xt = { class: "grid grid-cols-3 gap-3 text-center" }, St = { class: "rounded-lg bg-surface-secondary p-3" }, Ct = { class: "text-lg font-semibold text-heading" }, wt = { class: "rounded-lg bg-surface-secondary p-3" }, Tt = { class: "text-lg font-semibold text-heading" }, Et = { class: "rounded-lg bg-surface-secondary p-3" }, Dt = /* @__PURE__ */ c({
	__name: "TripCreatePage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(c) {
		let l = c, u = _([]), f = _(null), p = _(Ke()), m = _([]), h = _(!1), S = _(!1), T = _(null), D = _(!1);
		async function O() {
			h.value = !0;
			try {
				let e = await Re(l.client, "lr", f.value);
				u.value = e, m.value.length === 0 && e.length > 0 && (m.value = [{ receipt_id: e[0].id }]);
			} catch (e) {
				l.notify("error", F(e, "Unable to load the receipts."));
			} finally {
				h.value = !1;
			}
		}
		C(f, (e) => {
			if (e === null) {
				u.value = [], m.value = [], T.value = null;
				return;
			}
			O();
		});
		function k(e) {
			let t = m.value.map((t, n) => n === e ? null : t.receipt_id).filter((e) => e !== null);
			return u.value.filter((e) => !t.includes(e.id)).map((e) => ({
				id: e.id,
				label: `${e.invoice_number} · ${e.from_city ?? "—"} → ${e.to_city ?? "—"} · ${e.customer_name ?? "No customer"}`
			}));
		}
		let j = t(() => {
			let e = M.value;
			return u.value.some((t) => !e.includes(t.id));
		}), M = t(() => m.value.map((e) => e.receipt_id).filter((e) => e !== null));
		function ee() {
			let e = u.value.find((e) => !M.value.includes(e.id));
			m.value.push({ receipt_id: e?.id ?? null });
		}
		function te(e) {
			m.value.splice(e, 1);
		}
		function N() {
			m.value = [...u.value].sort((e, t) => (e.invoice_date ?? "9999-12-31").localeCompare(t.invoice_date ?? "9999-12-31") || e.id - t.id).map((e) => ({ receipt_id: e.id }));
		}
		function ne(e) {
			return u.value.find((t) => t.id === e)?.amount ?? 0;
		}
		let re = t(() => m.value.map((e) => u.value.find((t) => t.id === e.receipt_id)).filter((e) => e !== void 0)), ie = t(() => re.value.reduce((e, t) => e + t.amount, 0)), ae = t(() => T.value?.amount ?? 0), oe = t(() => ie.value - ae.value), se = t(() => new Set(re.value.map((e) => `${e.from_city}→${e.to_city}`)).size > 1);
		async function ce() {
			if (M.value.length === 0) {
				T.value = null;
				return;
			}
			D.value = !0;
			try {
				T.value = (await ze(l.client, M.value))[0] ?? null;
			} catch {
				T.value = null;
			} finally {
				D.value = !1;
			}
		}
		let le = null;
		C(M, () => {
			le && clearTimeout(le), le = setTimeout(() => {
				ce();
			}, 400);
		}, { deep: !0 });
		async function ue() {
			if (M.value.length === 0) {
				l.notify("warning", "Add at least one LR receipt.");
				return;
			}
			S.value = !0;
			try {
				let e = await Pe(l.client, M.value);
				l.notify("success", `Trip #${e.trip_no} created.`), l.router?.push(A.trip(e.id));
			} catch (e) {
				l.notify("error", F(e, "Unable to create the trip."));
			} finally {
				S.value = !1;
			}
		}
		return (t, c) => {
			let l = y("BaseBreadcrumbItem"), _ = y("BaseBreadcrumb"), C = y("BaseButton"), O = y("BasePageHeader"), ce = y("BaseCustomerSelectInput"), le = y("BaseInputGroup"), de = y("BaseInput"), fe = y("BaseInputGrid"), pe = y("BaseIcon"), me = y("BaseSelectInput"), he = y("BaseCard"), ge = y("BasePage");
			return g(), n(ge, { class: "relative" }, {
				default: w(() => [a("form", {
					class: "flex flex-col gap-4 md:gap-5",
					onSubmit: E(ue, ["prevent"])
				}, [s(O, { title: "New Trip" }, {
					actions: w(() => [s(C, {
						loading: S.value,
						disabled: S.value,
						variant: "primary",
						type: "submit"
					}, {
						default: w(() => [...c[2] ||= [o(" Create Trip ", -1)]]),
						_: 1
					}, 8, ["loading", "disabled"])]),
					default: w(() => [s(_, null, {
						default: w(() => [
							s(l, {
								title: "Home",
								to: "/admin/dashboard"
							}),
							s(l, {
								title: "Trips",
								to: x(A).board
							}, null, 8, ["to"]),
							s(l, {
								title: "New Trip",
								to: "#",
								active: ""
							})
						]),
						_: 1
					})]),
					_: 1
				}), s(he, { "container-class": "p-4 md:p-5" }, {
					default: w(() => [
						s(fe, null, {
							default: w(() => [s(le, { label: "Customer (filter)" }, {
								default: w(() => [s(ce, {
									modelValue: f.value,
									"onUpdate:modelValue": c[0] ||= (e) => f.value = e,
									"can-deselect": "",
									placeholder: "All customers"
								}, null, 8, ["modelValue"])]),
								_: 1
							}), s(le, { label: "Pickup date" }, {
								default: w(() => [s(de, {
									modelValue: p.value,
									"onUpdate:modelValue": c[1] ||= (e) => p.value = e,
									type: "date"
								}, null, 8, ["modelValue"])]),
								_: 1
							})]),
							_: 1
						}),
						a("section", qe, [
							a("div", Je, [c[5] ||= a("div", null, [a("h2", { class: "text-section font-semibold text-heading" }, "LR Receipts"), a("p", { class: "mt-1 text-sm text-muted" }, " Choose the LR receipts this trip carries. Pick several for a shared load. ")], -1), a("div", Ye, [s(C, {
								type: "button",
								size: "sm",
								variant: "primary-outline",
								disabled: h.value || u.value.length === 0,
								onClick: N
							}, {
								default: w(() => [...c[3] ||= [o(" Add Oldest First ", -1)]]),
								_: 1
							}, 8, ["disabled"]), s(C, {
								type: "button",
								size: "sm",
								variant: "primary-outline",
								disabled: h.value || !j.value,
								onClick: ee
							}, {
								left: w((e) => [s(pe, {
									name: "PlusIcon",
									class: d(e.class)
								}, null, 8, ["class"])]),
								default: w(() => [c[4] ||= o(" Add LR Receipt ", -1)]),
								_: 1
							}, 8, ["disabled"])])]),
							f.value === null ? (g(), i("div", Xe, " Select a customer first — the LR receipts for that customer will appear here. ")) : h.value ? (g(), i("div", Ze, " Loading the receipts… ")) : u.value.length === 0 ? (g(), i("div", Qe, " No unlinked LR receipts. Create one first, then start the trip. ")) : m.value.length === 0 ? (g(), i("div", $e, " Add the LR receipts this trip carries. ")) : (g(), i("div", et, [(g(!0), i(e, null, v(m.value, (e, t) => (g(), i("div", {
								key: t,
								class: "grid items-end gap-3 rounded-lg bg-surface-secondary p-3 md:grid-cols-[minmax(0,1fr)_10rem_auto]"
							}, [
								s(le, { label: "LR Receipt" }, {
									default: w(() => [s(me, {
										modelValue: e.receipt_id,
										"onUpdate:modelValue": (t) => e.receipt_id = t,
										options: k(t),
										"label-key": "label",
										placeholder: "Select an LR receipt"
									}, null, 8, [
										"modelValue",
										"onUpdate:modelValue",
										"options"
									])]),
									_: 2
								}, 1024),
								s(le, { label: "Amount" }, {
									default: w(() => [a("div", tt, b(x(I)(ne(e.receipt_id))), 1)]),
									_: 2
								}, 1024),
								a("div", nt, [s(C, {
									type: "button",
									size: "sm",
									variant: "gray",
									"aria-label": "Remove LR receipt",
									onClick: (e) => te(t)
								}, {
									default: w(() => [s(pe, { name: "TrashIcon" })]),
									_: 1
								}, 8, ["onClick"])])
							]))), 128))])),
							se.value ? (g(), i("p", rt, " ⚠ Different routes selected — same vehicle? ")) : r("", !0),
							a("div", it, [a("span", at, [c[6] ||= o(" Receipts: ", -1), a("span", ot, b(re.value.length), 1)]), a("span", st, [c[7] ||= o(" Revenue: ", -1), a("span", ct, b(x(I)(ie.value)), 1)])])
						]),
						a("section", lt, [c[9] ||= a("div", { class: "mb-4" }, [a("h2", { class: "text-section font-semibold text-heading" }, "Lorry Receipt"), a("p", { class: "mt-1 text-sm text-muted" }, " Auto-matched from the bilty numbers on the selected LR receipts — no need to pick it. ")], -1), f.value === null ? (g(), i("div", ut, " Select a customer first — the lorry receipt follows from the LR receipts. ")) : D.value ? (g(), i("div", dt, " Finding the lorry receipt… ")) : M.value.length === 0 ? (g(), i("div", ft, " Select an LR receipt first — the lorry receipt follows from its bilty numbers. ")) : T.value ? (g(), i("div", mt, [a("div", ht, [a("p", gt, [o(" 🚛 " + b(T.value.invoice_number) + " ", 1), c[8] ||= a("span", { class: "ms-2 rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700" }, " Auto-matched ", -1)]), a("p", _t, [o(b(T.value.from_city ?? "—") + " → " + b(T.value.to_city ?? "—") + " ", 1), T.value.customer_name ? (g(), i("span", vt, " · " + b(T.value.customer_name), 1)) : r("", !0)])]), a("p", yt, b(x(I)(T.value.amount)), 1)])) : (g(), i("div", pt, " No lorry receipt lists these bilty numbers yet. The trip starts without one; link it later from the trip page. "))]),
						a("section", bt, [c[13] ||= a("h2", { class: "mb-4 text-section font-semibold text-heading" }, "The Money", -1), a("div", xt, [
							a("div", St, [c[10] ||= a("p", { class: "text-xs text-muted" }, "Revenue", -1), a("p", Ct, b(x(I)(ie.value)), 1)]),
							a("div", wt, [c[11] ||= a("p", { class: "text-xs text-muted" }, "Cost", -1), a("p", Tt, b(x(I)(ae.value)), 1)]),
							a("div", Et, [c[12] ||= a("p", { class: "text-xs text-muted" }, "Profit", -1), a("p", { class: d(["text-lg font-semibold", oe.value >= 0 ? "text-status-green" : "text-status-red"]) }, b(x(I)(oe.value)), 3)])
						])])
					]),
					_: 1
				})], 32)]),
				_: 1
			});
		};
	}
}), Ot = { class: "space-y-3" }, kt = {
	key: 1,
	class: "py-8 text-center text-sm text-muted"
}, At = {
	key: 2,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted"
}, jt = {
	key: 3,
	class: "max-h-72 space-y-1 overflow-y-auto"
}, Mt = ["onClick"], Nt = ["checked", "onChange"], Pt = { class: "min-w-0 flex-1" }, Ft = { class: "block font-medium text-heading" }, It = { class: "block truncate text-xs text-muted" }, Lt = { class: "text-sm font-semibold text-heading" }, Rt = {
	key: 4,
	class: "text-sm text-muted"
}, zt = {
	key: 5,
	class: "text-sm text-alert-warning-text"
}, Bt = { class: "flex justify-end gap-2" }, Vt = /* @__PURE__ */ c({
	__name: "LrPickerModal",
	props: {
		show: { type: Boolean },
		client: { type: [Function, Object] },
		notify: { type: Function },
		type: {},
		multiple: { type: Boolean },
		customers: {}
	},
	emits: ["close", "select"],
	setup(c, { emit: l }) {
		let u = c, f = l, p = _([]), h = _([]), S = _(null), T = _(!1), D = t(() => u.multiple ?? !0);
		async function O() {
			T.value = !0;
			try {
				p.value = await Re(u.client, u.type ?? "lr", S.value), h.value = [];
			} catch (e) {
				u.notify("error", F(e, "Unable to load the receipts."));
			} finally {
				T.value = !1;
			}
		}
		m(() => {
			u.show && O();
		}), C(() => u.show, (e) => {
			e && O();
		}), C(S, () => {
			O();
		});
		function k(e) {
			if (!D.value) {
				h.value = [e];
				return;
			}
			h.value.includes(e) ? h.value = h.value.filter((t) => t !== e) : h.value = [...h.value, e];
		}
		let A = t(() => p.value.filter((e) => h.value.includes(e.id)).reduce((e, t) => e + t.amount, 0)), j = t(() => {
			let e = p.value.filter((e) => h.value.includes(e.id));
			return new Set(e.map((e) => `${e.from_city}→${e.to_city}`)).size > 1;
		});
		function M() {
			h.value.length !== 0 && f("select", [...h.value]);
		}
		return (t, l) => {
			let u = y("BaseSelectInput"), m = y("BaseInputGroup"), _ = y("BaseButton"), C = y("BaseModal");
			return g(), n(C, {
				show: c.show,
				onClose: l[2] ||= (e) => f("close")
			}, {
				header: w(() => [a("span", null, b(c.type === "lorry" ? "Map Lorry Receipt" : "Map LR Receipts"), 1)]),
				default: w(() => [a("div", Ot, [
					c.customers && c.customers.length > 0 ? (g(), n(m, {
						key: 0,
						label: "Customer"
					}, {
						default: w(() => [s(u, {
							modelValue: S.value,
							"onUpdate:modelValue": l[0] ||= (e) => S.value = e,
							options: [{
								id: null,
								label: "All customers"
							}, ...c.customers.map((e) => ({
								id: e.id,
								label: e.name
							}))],
							"label-key": "label"
						}, null, 8, ["modelValue", "options"])]),
						_: 1
					})) : r("", !0),
					T.value ? (g(), i("div", kt, "Loading…")) : p.value.length === 0 ? (g(), i("div", At, " No unlinked receipts. Create one first. ")) : (g(), i("ul", jt, [(g(!0), i(e, null, v(p.value, (e) => (g(), i("li", { key: e.id }, [a("label", {
						class: d(["flex cursor-pointer items-center gap-3 rounded-lg border border-line-default bg-surface p-2.5 text-sm hover:bg-hover", { "border-primary-500": h.value.includes(e.id) }]),
						onClick: E((t) => k(e.id), ["prevent"])
					}, [
						a("input", {
							type: "checkbox",
							class: "accent-primary-600",
							checked: h.value.includes(e.id),
							onChange: (t) => k(e.id)
						}, null, 40, Nt),
						a("span", Pt, [a("span", Ft, b(e.invoice_number) + " · " + b(e.from_city ?? "—") + " → " + b(e.to_city ?? "—"), 1), a("span", It, b(e.customer_name ?? "No customer") + " · " + b(e.goods ?? ""), 1)]),
						a("span", Lt, b(x(I)(e.amount)), 1)
					], 10, Mt)]))), 128))])),
					h.value.length > 0 ? (g(), i("p", Rt, " Selected: " + b(h.value.length) + " · " + b(x(I)(A.value)), 1)) : r("", !0),
					j.value ? (g(), i("p", zt, " ⚠ Different routes selected — same vehicle? ")) : r("", !0)
				])]),
				footer: w(() => [a("div", Bt, [s(_, {
					variant: "primary-outline",
					size: "sm",
					onClick: l[1] ||= (e) => f("close")
				}, {
					default: w(() => [...l[3] ||= [o("Cancel", -1)]]),
					_: 1
				}), s(_, {
					variant: "primary",
					size: "sm",
					disabled: h.value.length === 0,
					onClick: M
				}, {
					default: w(() => [o(b(D.value ? `Link ${h.value.length}` : "Link"), 1)]),
					_: 1
				}, 8, ["disabled"])])]),
				_: 1
			}, 8, ["show"]);
		};
	}
}), Ht = { class: "min-h-screen bg-surface-secondary" }, Ut = { class: "mx-auto max-w-5xl p-4 lg:p-6" }, Wt = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, Gt = { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, Kt = { class: "text-xl font-semibold text-heading" }, qt = { class: "flex items-center gap-2" }, Jt = ["disabled"], Yt = ["value"], Xt = {
	key: 0,
	class: "mb-4 rounded-lg bg-alert-error-bg p-3 text-sm text-alert-error-text"
}, Zt = { class: "space-y-4" }, Qt = { class: "space-y-2" }, $t = { class: "min-w-0" }, en = { class: "font-medium text-heading" }, tn = { class: "truncate text-xs text-muted" }, nn = { class: "flex shrink-0 gap-1" }, rn = {
	key: 0,
	class: "text-sm text-muted"
}, an = { class: "flex gap-2 pt-1" }, on = { class: "mt-4 border-t border-line-light pt-3" }, sn = {
	key: 0,
	class: "flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
}, cn = { class: "min-w-0" }, ln = { class: "font-medium text-heading" }, un = { class: "truncate text-xs text-muted" }, dn = { class: "flex shrink-0 gap-1" }, fn = {
	key: 1,
	class: "flex items-center gap-2"
}, pn = { class: "mt-4 border-t border-line-light pt-3" }, mn = { class: "min-w-0" }, hn = { class: "font-medium text-heading" }, gn = { class: "truncate text-xs text-muted" }, _n = { class: "flex shrink-0 gap-1" }, vn = {
	key: 0,
	class: "flex items-center gap-2"
}, yn = {
	key: 0,
	class: "text-xs text-muted"
}, bn = { class: "mt-4 border-t border-line-light pt-3" }, xn = { class: "mb-2 text-sm font-semibold text-heading" }, Sn = { class: "grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" }, Cn = { class: "mb-1.5 text-xs font-medium text-muted" }, wn = {
	key: 0,
	class: "group relative flex h-28 items-center justify-center overflow-hidden rounded-lg border border-line-default bg-surface-secondary"
}, Tn = ["src", "alt"], En = {
	key: 1,
	class: "flex flex-col items-center gap-1 text-subtle"
}, Dn = {
	key: 2,
	class: "flex items-center justify-center text-subtle"
}, On = { class: "absolute inset-0 flex items-center justify-center gap-1 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100" }, kn = ["onClick"], An = ["disabled", "onClick"], jn = {
	key: 2,
	class: "mt-1 flex items-center justify-between gap-1"
}, Mn = { class: "min-w-0 truncate text-xs text-muted" }, Nn = ["onClick"], Pn = { class: "mt-4 border-t border-line-light pt-3" }, Fn = { class: "grid grid-cols-3 gap-3 text-center" }, In = { class: "rounded-lg bg-surface-secondary p-3" }, Ln = { class: "text-lg font-semibold text-heading" }, Rn = { class: "rounded-lg bg-surface-secondary p-3" }, zn = { class: "text-lg font-semibold text-heading" }, Bn = { class: "rounded-lg bg-surface-secondary p-3" }, Vn = {
	key: 0,
	class: "mt-3"
}, Hn = { class: "flex items-center justify-between gap-2" }, Un = { class: "min-w-0" }, Wn = { class: "font-medium text-heading" }, Gn = { class: "truncate text-xs text-muted" }, Kn = {
	key: 0,
	class: "mt-2 space-y-1"
}, qn = { class: "text-muted" }, Jn = { class: "font-medium text-heading" }, Yn = {
	key: 1,
	class: "mt-3"
}, Xn = { class: "flex items-center justify-between gap-2" }, Zn = { class: "min-w-0" }, Qn = { class: "font-medium text-heading" }, $n = { class: "truncate text-xs text-muted" }, er = { key: 0 }, tr = {
	key: 0,
	class: "mt-1 text-xs text-muted"
}, nr = {
	key: 1,
	class: "mt-2 space-y-1"
}, rr = { class: "text-muted" }, ir = { key: 0 }, ar = { class: "font-medium text-heading" }, or = { class: "mt-3 flex items-center justify-between rounded-lg border border-line-light p-2.5 text-sm" }, sr = { class: "text-muted" }, cr = { class: "font-semibold text-heading" }, lr = ["title"], ur = /* @__PURE__ */ c({
	__name: "TripDetailPage",
	props: {
		id: {},
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(c) {
		let l = c, u = _(null), p = _(!0), E = _([]), D = _(!1), O = _(!1), k = _(!1), j = _(!1), M = _(null), ee = _(null), te = _(""), N = t(() => Number(l.id)), ne = t({
			get: () => u.value?.status.id ?? null,
			set: (e) => {
				e !== null && me(e);
			}
		}), re = t(() => u.value?.receipts.filter((e) => e.type === "lr") ?? []), ie = t(() => u.value?.receipts.find((e) => e.type === "lorry") ?? null), ae = t(() => u.value?.receipts.filter((e) => e.type === "invoice") ?? []), oe = [
			{
				role: "driver",
				label: "Driver Documents",
				slots: [
					{
						key: "driver_aadhar",
						label: "Aadhar Card"
					},
					{
						key: "driver_license",
						label: "Driving Licence"
					},
					{
						key: "driver_pan",
						label: "PAN Card"
					}
				]
			},
			{
				role: "owner",
				label: "Owner Documents",
				slots: [
					{
						key: "owner_aadhar",
						label: "Aadhar Card"
					},
					{
						key: "owner_pan",
						label: "PAN Card"
					},
					{
						key: "owner_rc",
						label: "Vehicle RC"
					}
				]
			},
			{
				role: "broker",
				label: "Broker Documents",
				slots: [{
					key: "broker_aadhar",
					label: "Aadhar Card"
				}, {
					key: "broker_pan",
					label: "PAN Card"
				}]
			}
		];
		function se(e) {
			return u.value?.documents.find((t) => t.category === e) ?? null;
		}
		let ce = _({});
		function le() {
			for (let e of Object.values(ce.value)) URL.revokeObjectURL(e);
			ce.value = {};
		}
		async function ue() {
			if (u.value) {
				le();
				for (let e of u.value.documents) try {
					let t = await We(l.client, u.value.id, e.id);
					ce.value[e.id] = URL.createObjectURL(t);
				} catch {}
			}
		}
		function de(e) {
			let t = ce.value[e];
			t && window.open(t, "_blank", "noopener");
		}
		C(() => u.value?.documents, () => {
			ue();
		});
		async function fe() {
			p.value = !0;
			try {
				u.value = await Ne(l.client, N.value);
			} catch (e) {
				l.notify("error", F(e, "Unable to load the trip."));
			} finally {
				p.value = !1;
			}
		}
		async function pe() {
			try {
				E.value = await Me(l.client);
			} catch {}
		}
		async function me(e) {
			if (!(!u.value || D.value)) {
				D.value = !0;
				try {
					u.value = await Le(l.client, u.value.id, e), l.notify("success", "Status updated.");
				} catch (e) {
					l.notify("error", F(e, "Unable to change the status."));
				} finally {
					D.value = !1;
				}
			}
		}
		function he() {
			document.hidden || fe();
		}
		m(() => {
			fe(), pe(), document.addEventListener("visibilitychange", he);
		}), h(() => {
			le(), document.removeEventListener("visibilitychange", he);
		});
		async function ge() {
			if (!(!u.value || !window.confirm("Cancel this trip?"))) try {
				u.value = await Ie(l.client, u.value.id), l.notify("success", "Trip cancelled.");
			} catch (e) {
				l.notify("error", F(e, "Unable to cancel the trip."));
			}
		}
		async function _e(e) {
			if (u.value) {
				O.value = !1;
				try {
					for (let t of e) u.value = await Be(l.client, u.value.id, t, "lr");
					l.notify("success", "LR receipt linked.");
				} catch (e) {
					l.notify("error", F(e, "Unable to link the receipt."));
				}
			}
		}
		async function ve(e) {
			if (!(!u.value || e.length === 0)) {
				k.value = !1;
				try {
					u.value = await Be(l.client, u.value.id, e[0], "lorry"), l.notify("success", "Lorry receipt linked.");
				} catch (e) {
					l.notify("error", F(e, "Unable to link the receipt."));
				}
			}
		}
		async function ye(e) {
			if (u.value) try {
				u.value = await Ge(l.client, u.value.id, e), l.notify("success", "Receipt unlinked.");
			} catch (e) {
				l.notify("error", F(e, "Unable to unlink the receipt."));
			}
		}
		async function be() {
			if (u.value) {
				j.value = !0;
				try {
					u.value = await Ve(l.client, u.value.id), l.notify("success", "Invoice receipt fetched and linked.");
				} catch (e) {
					l.notify("error", F(e, "No invoice receipt found for these LR numbers."));
				} finally {
					j.value = !1;
				}
			}
		}
		function xe(e) {
			te.value = e, ee.value?.click();
		}
		async function Se(e) {
			if (!u.value) return;
			let t = e.target, n = t.files;
			if (!n || n.length === 0) return;
			let r = te.value;
			M.value = r;
			try {
				u.value = await He(l.client, u.value.id, n[0], r), l.notify("success", "Document uploaded.");
			} catch (e) {
				l.notify("error", F(e, "Unable to upload the document."));
			} finally {
				M.value = null, t.value = "";
			}
		}
		async function Ce(e) {
			if (u.value) try {
				u.value = await Ue(l.client, u.value.id, e), l.notify("success", "Document removed.");
			} catch (e) {
				l.notify("error", F(e, "Unable to remove the document."));
			}
		}
		function we(e) {
			l.router?.push(A.invoiceView(e));
		}
		function Te() {
			l.router?.push(A.board);
		}
		return (t, l) => {
			let m = y("BaseButton"), h = y("BaseSettingCard");
			return g(), i("div", Ht, [
				a("div", Ut, [p.value ? (g(), i("div", Wt, "Loading the trip…")) : u.value ? (g(), i(e, { key: 1 }, [
					a("div", Gt, [a("div", null, [a("button", {
						type: "button",
						class: "text-sm text-muted hover:text-heading",
						onClick: Te
					}, " ← Trips "), a("h1", Kt, " Trip #" + b(u.value.trip_no) + " · " + b(u.value.from_city ?? "—") + " → " + b(u.value.to_city ?? "—"), 1)]), a("div", qt, [E.value.length > 0 && !u.value.cancelled_at ? T((g(), i("select", {
						key: 0,
						"onUpdate:modelValue": l[0] ||= (e) => ne.value = e,
						disabled: D.value,
						class: "rounded-full border-0 px-3 py-1 text-xs font-semibold text-white outline-none",
						style: f({ backgroundColor: u.value.status.colour ?? "#94a3b8" })
					}, [(g(!0), i(e, null, v(E.value, (e) => (g(), i("option", {
						key: e.id,
						value: e.id,
						class: "bg-surface text-body"
					}, b(e.name), 9, Yt))), 128))], 12, Jt)), [[S, ne.value]]) : (g(), i("span", {
						key: 1,
						class: "rounded-full px-3 py-1 text-xs font-semibold text-white",
						style: f({ backgroundColor: u.value.status.colour ?? "#94a3b8" })
					}, b(u.value.status.name), 5)), u.value.cancelled_at ? r("", !0) : (g(), n(m, {
						key: 2,
						size: "sm",
						variant: "danger",
						onClick: ge
					}, {
						default: w(() => [...l[7] ||= [o(" Cancel Trip ", -1)]]),
						_: 1
					}))])]),
					u.value.cancelled_at ? (g(), i("div", Xt, " This trip is cancelled. ")) : r("", !0),
					a("div", Zt, [s(h, {
						title: "Documents",
						description: "LR receipts, lorry receipt, invoice receipt and trip documents for this trip."
					}, {
						default: w(() => [
							a("div", Qt, [
								(g(!0), i(e, null, v(re.value, (e) => (g(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", $t, [a("p", en, "📄 " + b(e.invoice_number), 1), a("p", tn, b(e.customer_name ?? "No customer") + " · " + b(x(I)(e.amount)), 1)]), a("div", nn, [s(m, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => we(e.invoice_id)
								}, {
									default: w(() => [...l[8] ||= [o(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (g(), n(m, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => ye(e.id)
								}, {
									default: w(() => [...l[9] ||= [o(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								re.value.length === 0 ? (g(), i("p", rn, "No LR receipts linked yet.")) : r("", !0),
								a("div", an, [s(m, {
									size: "sm",
									variant: "primary-outline",
									onClick: l[1] ||= (e) => O.value = !0
								}, {
									default: w(() => [...l[10] ||= [o(" + Link LR ", -1)]]),
									_: 1
								})])
							]),
							a("div", on, [l[14] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "Lorry Receipt", -1), ie.value ? (g(), i("div", sn, [a("div", cn, [a("p", ln, "🚛 " + b(ie.value.invoice_number), 1), a("p", un, b(ie.value.customer_name ?? "No customer") + " · " + b(x(I)(ie.value.amount)), 1)]), a("div", dn, [s(m, {
								size: "sm",
								variant: "primary-outline",
								onClick: l[2] ||= (e) => we(ie.value.invoice_id)
							}, {
								default: w(() => [...l[11] ||= [o(" View ", -1)]]),
								_: 1
							}), ie.value.billed_invoice_id ? r("", !0) : (g(), n(m, {
								key: 0,
								size: "sm",
								variant: "danger-outline",
								onClick: l[3] ||= (e) => ye(ie.value.id)
							}, {
								default: w(() => [...l[12] ||= [o(" Unlink ", -1)]]),
								_: 1
							}))])])) : (g(), i("div", fn, [s(m, {
								size: "sm",
								variant: "primary-outline",
								onClick: l[4] ||= (e) => k.value = !0
							}, {
								default: w(() => [...l[13] ||= [o(" + Link Lorry Receipt ", -1)]]),
								_: 1
							})]))]),
							a("div", pn, [
								l[17] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "Invoice Receipt", -1),
								(g(!0), i(e, null, v(ae.value, (e) => (g(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", mn, [a("p", hn, "🧾 " + b(e.invoice_number), 1), a("p", gn, b(e.customer_name ?? "No customer") + " · " + b(x(I)(e.amount)), 1)]), a("div", _n, [s(m, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => we(e.invoice_id)
								}, {
									default: w(() => [...l[15] ||= [o(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (g(), n(m, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => ye(e.id)
								}, {
									default: w(() => [...l[16] ||= [o(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								ae.value.length === 0 ? (g(), i("div", vn, [s(m, {
									size: "sm",
									variant: "primary-outline",
									disabled: j.value || re.value.length === 0,
									onClick: be
								}, {
									default: w(() => [o(b(j.value ? "Fetching…" : "Fetch Invoice Receipt"), 1)]),
									_: 1
								}, 8, ["disabled"]), re.value.length === 0 ? (g(), i("span", yn, "Link an LR first.")) : r("", !0)])) : r("", !0)
							]),
							a("div", bn, [
								l[26] ||= a("p", { class: "mb-3 text-xs font-semibold uppercase tracking-wide text-muted" }, "Trip Documents", -1),
								a("input", {
									ref_key: "podFileInput",
									ref: ee,
									type: "file",
									class: "hidden",
									accept: "image/*,application/pdf",
									onChange: Se
								}, null, 544),
								(g(), i(e, null, v(oe, (t) => a("div", {
									key: t.role,
									class: "mb-4 last:mb-0"
								}, [a("p", xn, b(t.label), 1), a("div", Sn, [(g(!0), i(e, null, v(t.slots, (t) => (g(), i("div", {
									key: t.key,
									class: "flex flex-col"
								}, [
									a("p", Cn, b(t.label), 1),
									se(t.key) ? (g(), i("div", wn, [se(t.key).is_image && ce.value[se(t.key).id] ? (g(), i("img", {
										key: 0,
										src: ce.value[se(t.key).id],
										alt: t.label,
										class: "h-full w-full object-cover"
									}, null, 8, Tn)) : se(t.key).is_image ? (g(), i("div", Dn, [...l[19] ||= [a("svg", {
										class: "h-5 w-5 animate-spin",
										fill: "none",
										viewBox: "0 0 24 24"
									}, [a("circle", {
										class: "opacity-25",
										cx: "12",
										cy: "12",
										r: "10",
										stroke: "currentColor",
										"stroke-width": "4"
									}), a("path", {
										class: "opacity-75",
										fill: "currentColor",
										d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
									})], -1)]])) : (g(), i("div", En, [...l[18] ||= [a("svg", {
										class: "h-8 w-8",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "1.25",
										d: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
									})], -1), a("span", { class: "text-xs" }, "PDF", -1)]])), a("div", On, [s(m, {
										size: "sm",
										variant: "primary",
										onClick: (e) => de(se(t.key).id)
									}, {
										default: w(() => [...l[20] ||= [o(" View ", -1)]]),
										_: 1
									}, 8, ["onClick"]), a("button", {
										type: "button",
										class: "flex h-7 w-7 items-center justify-center rounded-full bg-surface text-status-red shadow",
										"aria-label": "Remove",
										onClick: (e) => Ce(se(t.key).id)
									}, [...l[21] ||= [a("svg", {
										class: "h-4 w-4",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "2",
										d: "M6 18L18 6M6 6l12 12"
									})], -1)]], 8, kn)])])) : (g(), i("button", {
										key: 1,
										type: "button",
										class: "flex h-28 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-line-strong bg-surface-secondary text-subtle transition-colors hover:border-primary-400 hover:text-primary-500",
										disabled: M.value === t.key,
										onClick: (e) => xe(t.key)
									}, [M.value === t.key ? (g(), i(e, { key: 0 }, [l[22] ||= a("svg", {
										class: "h-5 w-5 animate-spin",
										fill: "none",
										viewBox: "0 0 24 24"
									}, [a("circle", {
										class: "opacity-25",
										cx: "12",
										cy: "12",
										r: "10",
										stroke: "currentColor",
										"stroke-width": "4"
									}), a("path", {
										class: "opacity-75",
										fill: "currentColor",
										d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
									})], -1), l[23] ||= a("span", { class: "text-xs" }, "Uploading…", -1)], 64)) : (g(), i(e, { key: 1 }, [l[24] ||= a("svg", {
										class: "h-6 w-6",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "1.5",
										d: "M12 4v16m8-8H4"
									})], -1), l[25] ||= a("span", { class: "text-xs" }, "Upload", -1)], 64))], 8, An)),
									se(t.key) ? (g(), i("div", jn, [a("span", Mn, b(se(t.key).file_name), 1), a("button", {
										type: "button",
										class: "shrink-0 text-xs text-primary-500 hover:underline",
										onClick: (e) => xe(t.key)
									}, " Replace ", 8, Nn)])) : r("", !0)
								]))), 128))])])), 64))
							]),
							a("div", Pn, [
								l[32] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "The Money", -1),
								a("div", Fn, [
									a("div", In, [l[27] ||= a("p", { class: "text-xs text-muted" }, "Revenue", -1), a("p", Ln, b(x(I)(u.value.money.revenue)), 1)]),
									a("div", Rn, [l[28] ||= a("p", { class: "text-xs text-muted" }, "Cost", -1), a("p", zn, b(x(I)(u.value.money.cost)), 1)]),
									a("div", Bn, [l[29] ||= a("p", { class: "text-xs text-muted" }, "Profit", -1), a("p", { class: d(["text-lg font-semibold", u.value.money.profit >= 0 ? "text-status-green" : "text-status-red"]) }, b(x(I)(u.value.money.profit)), 3)])
								]),
								u.value.money.receivable.length > 0 ? (g(), i("div", Vn, [l[30] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "From Customer", -1), (g(!0), i(e, null, v(u.value.money.receivable, (t) => (g(), i("div", {
									key: t.invoice_id,
									class: "mb-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", Hn, [a("div", Un, [a("p", Wn, "🧾 " + b(t.invoice_number), 1), a("p", Gn, b(t.customer_name ?? "No customer") + " · Total " + b(x(I)(t.total)) + " · Due " + b(x(I)(t.due_amount)), 1)]), a("span", { class: d(["shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold", {
									"bg-status-red/10 text-status-red": t.paid_status === "UNPAID",
									"bg-status-yellow/10 text-status-yellow": t.paid_status === "PARTIALLY_PAID",
									"bg-status-green/10 text-status-green": t.paid_status === "PAID"
								}]) }, b(t.paid_status), 3)]), t.payments.length > 0 ? (g(), i("div", Kn, [(g(!0), i(e, null, v(t.payments, (e) => (g(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded bg-surface-secondary px-2 py-1 text-xs"
								}, [a("span", qn, b(e.number ?? "—") + " · " + b(e.date ?? "—"), 1), a("span", Jn, b(x(I)(e.amount)), 1)]))), 128))])) : r("", !0)]))), 128))])) : r("", !0),
								u.value.money.payable.length > 0 ? (g(), i("div", Yn, [l[31] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "To Owner", -1), (g(!0), i(e, null, v(u.value.money.payable, (t) => (g(), i("div", {
									key: t.invoice_number,
									class: "mb-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [
									a("div", Xn, [a("div", Zn, [a("p", Qn, "🚛 " + b(t.bill_number ?? t.invoice_number), 1), a("p", $n, [o(b(t.supplier_name ?? "Unknown supplier"), 1), t.bill_reference ? (g(), i("span", er, " · " + b(t.bill_reference), 1)) : r("", !0)])]), t.bill_status ? (g(), i("span", {
										key: 0,
										class: d(["shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold", {
											"bg-status-red/10 text-status-red": t.bill_status === "UNPAID",
											"bg-status-yellow/10 text-status-yellow": t.bill_status === "PARTIAL",
											"bg-status-green/10 text-status-green": t.bill_status === "SETTLED"
										}])
									}, b(t.bill_status), 3)) : r("", !0)]),
									t.bill_total === null ? r("", !0) : (g(), i("p", tr, " Total " + b(x(I)(t.bill_total)) + " · Due " + b(x(I)(t.bill_due ?? 0)), 1)),
									t.payments.length > 0 ? (g(), i("div", nr, [(g(!0), i(e, null, v(t.payments, (e) => (g(), i("div", {
										key: e.id,
										class: "flex items-center justify-between gap-2 rounded bg-surface-secondary px-2 py-1 text-xs"
									}, [a("span", rr, [o(b(e.type === "advance" ? "Advance" : "Final") + " · " + b(e.date ?? "—"), 1), e.reference ? (g(), i("span", ir, " · " + b(e.reference), 1)) : r("", !0)]), a("span", ar, b(x(I)(e.amount)), 1)]))), 128))])) : r("", !0)
								]))), 128))])) : r("", !0),
								a("div", or, [a("span", sr, [o(" Company paid " + b(x(I)(u.value.money.owner_paid)) + " · balance ", 1), a("span", cr, b(x(I)(u.value.money.owner_balance)), 1)]), u.value.money.bill_status ? (g(), i("span", {
									key: 0,
									class: d(["rounded-full px-2 py-0.5 text-xs font-semibold", {
										"bg-status-red/10 text-status-red": u.value.money.bill_status === "UNPAID",
										"bg-status-yellow/10 text-status-yellow": u.value.money.bill_status === "PARTIAL",
										"bg-status-green/10 text-status-green": u.value.money.bill_status === "SETTLED"
									}]),
									title: `Bill ${u.value.money.bill_number}`
								}, b(u.value.money.bill_status), 11, lr)) : r("", !0)])
							])
						]),
						_: 1
					})])
				], 64)) : r("", !0)]),
				s(Vt, {
					show: O.value,
					client: c.client,
					notify: c.notify,
					multiple: "",
					onClose: l[5] ||= (e) => O.value = !1,
					onSelect: _e
				}, null, 8, [
					"show",
					"client",
					"notify"
				]),
				s(Vt, {
					show: k.value,
					client: c.client,
					notify: c.notify,
					type: "lorry",
					multiple: !1,
					onClose: l[6] ||= (e) => k.value = !1,
					onSelect: ve
				}, null, 8, [
					"show",
					"client",
					"notify"
				])
			]);
		};
	}
});
//#endregion
//#region ../../node_modules/sortablejs/modular/sortable.esm.js
function dr(e, t) {
	var n = Object.keys(e);
	if (Object.getOwnPropertySymbols) {
		var r = Object.getOwnPropertySymbols(e);
		t && (r = r.filter(function(t) {
			return Object.getOwnPropertyDescriptor(e, t).enumerable;
		})), n.push.apply(n, r);
	}
	return n;
}
function fr(e) {
	for (var t = 1; t < arguments.length; t++) {
		var n = arguments[t] == null ? {} : arguments[t];
		t % 2 ? dr(Object(n), !0).forEach(function(t) {
			mr(e, t, n[t]);
		}) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(n)) : dr(Object(n)).forEach(function(t) {
			Object.defineProperty(e, t, Object.getOwnPropertyDescriptor(n, t));
		});
	}
	return e;
}
function pr(e) {
	"@babel/helpers - typeof";
	return pr = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function(e) {
		return typeof e;
	} : function(e) {
		return e && typeof Symbol == "function" && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e;
	}, pr(e);
}
function mr(e, t, n) {
	return t in e ? Object.defineProperty(e, t, {
		value: n,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[t] = n, e;
}
function hr() {
	return hr = Object.assign || function(e) {
		for (var t = 1; t < arguments.length; t++) {
			var n = arguments[t];
			for (var r in n) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
		}
		return e;
	}, hr.apply(this, arguments);
}
function gr(e, t) {
	if (e == null) return {};
	var n = {}, r = Object.keys(e), i, a;
	for (a = 0; a < r.length; a++) i = r[a], !(t.indexOf(i) >= 0) && (n[i] = e[i]);
	return n;
}
function _r(e, t) {
	if (e == null) return {};
	var n = gr(e, t), r, i;
	if (Object.getOwnPropertySymbols) {
		var a = Object.getOwnPropertySymbols(e);
		for (i = 0; i < a.length; i++) r = a[i], !(t.indexOf(r) >= 0) && Object.prototype.propertyIsEnumerable.call(e, r) && (n[r] = e[r]);
	}
	return n;
}
var vr = "1.14.0";
function yr(e) {
	if (typeof window < "u" && window.navigator) return !!/* @__PURE__ */ navigator.userAgent.match(e);
}
var br = yr(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i), xr = yr(/Edge/i), Sr = yr(/firefox/i), Cr = yr(/safari/i) && !yr(/chrome/i) && !yr(/android/i), wr = yr(/iP(ad|od|hone)/i), Tr = yr(/chrome/i) && yr(/android/i), Er = {
	capture: !1,
	passive: !1
};
function L(e, t, n) {
	e.addEventListener(t, n, !br && Er);
}
function R(e, t, n) {
	e.removeEventListener(t, n, !br && Er);
}
function Dr(e, t) {
	if (t) {
		if (t[0] === ">" && (t = t.substring(1)), e) try {
			if (e.matches) return e.matches(t);
			if (e.msMatchesSelector) return e.msMatchesSelector(t);
			if (e.webkitMatchesSelector) return e.webkitMatchesSelector(t);
		} catch {
			return !1;
		}
		return !1;
	}
}
function Or(e) {
	return e.host && e !== document && e.host.nodeType ? e.host : e.parentNode;
}
function kr(e, t, n, r) {
	if (e) {
		n ||= document;
		do {
			if (t != null && (t[0] === ">" ? e.parentNode === n && Dr(e, t) : Dr(e, t)) || r && e === n) return e;
			if (e === n) break;
		} while (e = Or(e));
	}
	return null;
}
var Ar = /\s+/g;
function jr(e, t, n) {
	e && t && (e.classList ? e.classList[n ? "add" : "remove"](t) : e.className = ((" " + e.className + " ").replace(Ar, " ").replace(" " + t + " ", " ") + (n ? " " + t : "")).replace(Ar, " "));
}
function z(e, t, n) {
	var r = e && e.style;
	if (r) {
		if (n === void 0) return document.defaultView && document.defaultView.getComputedStyle ? n = document.defaultView.getComputedStyle(e, "") : e.currentStyle && (n = e.currentStyle), t === void 0 ? n : n[t];
		!(t in r) && t.indexOf("webkit") === -1 && (t = "-webkit-" + t), r[t] = n + (typeof n == "string" ? "" : "px");
	}
}
function Mr(e, t) {
	var n = "";
	if (typeof e == "string") n = e;
	else do {
		var r = z(e, "transform");
		r && r !== "none" && (n = r + " " + n);
	} while (!t && (e = e.parentNode));
	var i = window.DOMMatrix || window.WebKitCSSMatrix || window.CSSMatrix || window.MSCSSMatrix;
	return i && new i(n);
}
function Nr(e, t, n) {
	if (e) {
		var r = e.getElementsByTagName(t), i = 0, a = r.length;
		if (n) for (; i < a; i++) n(r[i], i);
		return r;
	}
	return [];
}
function Pr() {
	return document.scrollingElement || document.documentElement;
}
function B(e, t, n, r, i) {
	if (!(!e.getBoundingClientRect && e !== window)) {
		var a, o, s, c, l, u, d;
		if (e !== window && e.parentNode && e !== Pr() ? (a = e.getBoundingClientRect(), o = a.top, s = a.left, c = a.bottom, l = a.right, u = a.height, d = a.width) : (o = 0, s = 0, c = window.innerHeight, l = window.innerWidth, u = window.innerHeight, d = window.innerWidth), (t || n) && e !== window && (i ||= e.parentNode, !br)) do
			if (i && i.getBoundingClientRect && (z(i, "transform") !== "none" || n && z(i, "position") !== "static")) {
				var f = i.getBoundingClientRect();
				o -= f.top + parseInt(z(i, "border-top-width")), s -= f.left + parseInt(z(i, "border-left-width")), c = o + a.height, l = s + a.width;
				break;
			}
		while (i = i.parentNode);
		if (r && e !== window) {
			var p = Mr(i || e), m = p && p.a, h = p && p.d;
			p && (o /= h, s /= m, d /= m, u /= h, c = o + u, l = s + d);
		}
		return {
			top: o,
			left: s,
			bottom: c,
			right: l,
			width: d,
			height: u
		};
	}
}
function Fr(e, t, n) {
	for (var r = Vr(e, !0), i = B(e)[t]; r;) {
		var a = B(r)[n], o = void 0;
		if (o = n === "top" || n === "left" ? i >= a : i <= a, !o) return r;
		if (r === Pr()) break;
		r = Vr(r, !1);
	}
	return !1;
}
function Ir(e, t, n, r) {
	for (var i = 0, a = 0, o = e.children; a < o.length;) {
		if (o[a].style.display !== "none" && o[a] !== Q.ghost && (r || o[a] !== Q.dragged) && kr(o[a], n.draggable, e, !1)) {
			if (i === t) return o[a];
			i++;
		}
		a++;
	}
	return null;
}
function Lr(e, t) {
	for (var n = e.lastElementChild; n && (n === Q.ghost || z(n, "display") === "none" || t && !Dr(n, t));) n = n.previousElementSibling;
	return n || null;
}
function Rr(e, t) {
	var n = 0;
	if (!e || !e.parentNode) return -1;
	for (; e = e.previousElementSibling;) e.nodeName.toUpperCase() !== "TEMPLATE" && e !== Q.clone && (!t || Dr(e, t)) && n++;
	return n;
}
function zr(e) {
	var t = 0, n = 0, r = Pr();
	if (e) do {
		var i = Mr(e), a = i.a, o = i.d;
		t += e.scrollLeft * a, n += e.scrollTop * o;
	} while (e !== r && (e = e.parentNode));
	return [t, n];
}
function Br(e, t) {
	for (var n in e) if (e.hasOwnProperty(n)) {
		for (var r in t) if (t.hasOwnProperty(r) && t[r] === e[n][r]) return Number(n);
	}
	return -1;
}
function Vr(e, t) {
	if (!e || !e.getBoundingClientRect) return Pr();
	var n = e, r = !1;
	do
		if (n.clientWidth < n.scrollWidth || n.clientHeight < n.scrollHeight) {
			var i = z(n);
			if (n.clientWidth < n.scrollWidth && (i.overflowX == "auto" || i.overflowX == "scroll") || n.clientHeight < n.scrollHeight && (i.overflowY == "auto" || i.overflowY == "scroll")) {
				if (!n.getBoundingClientRect || n === document.body) return Pr();
				if (r || t) return n;
				r = !0;
			}
		}
	while (n = n.parentNode);
	return Pr();
}
function Hr(e, t) {
	if (e && t) for (var n in t) t.hasOwnProperty(n) && (e[n] = t[n]);
	return e;
}
function Ur(e, t) {
	return Math.round(e.top) === Math.round(t.top) && Math.round(e.left) === Math.round(t.left) && Math.round(e.height) === Math.round(t.height) && Math.round(e.width) === Math.round(t.width);
}
var Wr;
function Gr(e, t) {
	return function() {
		if (!Wr) {
			var n = arguments, r = this;
			n.length === 1 ? e.call(r, n[0]) : e.apply(r, n), Wr = setTimeout(function() {
				Wr = void 0;
			}, t);
		}
	};
}
function Kr() {
	clearTimeout(Wr), Wr = void 0;
}
function qr(e, t, n) {
	e.scrollLeft += t, e.scrollTop += n;
}
function Jr(e) {
	var t = window.Polymer, n = window.jQuery || window.Zepto;
	return t && t.dom ? t.dom(e).cloneNode(!0) : n ? n(e).clone(!0)[0] : e.cloneNode(!0);
}
var V = "Sortable" + (/* @__PURE__ */ new Date()).getTime();
function Yr() {
	var e = [], t;
	return {
		captureAnimationState: function() {
			e = [], this.options.animation && [].slice.call(this.el.children).forEach(function(t) {
				if (!(z(t, "display") === "none" || t === Q.ghost)) {
					e.push({
						target: t,
						rect: B(t)
					});
					var n = fr({}, e[e.length - 1].rect);
					if (t.thisAnimationDuration) {
						var r = Mr(t, !0);
						r && (n.top -= r.f, n.left -= r.e);
					}
					t.fromRect = n;
				}
			});
		},
		addAnimationState: function(t) {
			e.push(t);
		},
		removeAnimationState: function(t) {
			e.splice(Br(e, { target: t }), 1);
		},
		animateAll: function(n) {
			var r = this;
			if (!this.options.animation) {
				clearTimeout(t), typeof n == "function" && n();
				return;
			}
			var i = !1, a = 0;
			e.forEach(function(e) {
				var t = 0, n = e.target, o = n.fromRect, s = B(n), c = n.prevFromRect, l = n.prevToRect, u = e.rect, d = Mr(n, !0);
				d && (s.top -= d.f, s.left -= d.e), n.toRect = s, n.thisAnimationDuration && Ur(c, s) && !Ur(o, s) && (u.top - s.top) / (u.left - s.left) === (o.top - s.top) / (o.left - s.left) && (t = Zr(u, c, l, r.options)), Ur(s, o) || (n.prevFromRect = o, n.prevToRect = s, t ||= r.options.animation, r.animate(n, u, s, t)), t && (i = !0, a = Math.max(a, t), clearTimeout(n.animationResetTimer), n.animationResetTimer = setTimeout(function() {
					n.animationTime = 0, n.prevFromRect = null, n.fromRect = null, n.prevToRect = null, n.thisAnimationDuration = null;
				}, t), n.thisAnimationDuration = t);
			}), clearTimeout(t), i ? t = setTimeout(function() {
				typeof n == "function" && n();
			}, a) : typeof n == "function" && n(), e = [];
		},
		animate: function(e, t, n, r) {
			if (r) {
				z(e, "transition", ""), z(e, "transform", "");
				var i = Mr(this.el), a = i && i.a, o = i && i.d, s = (t.left - n.left) / (a || 1), c = (t.top - n.top) / (o || 1);
				e.animatingX = !!s, e.animatingY = !!c, z(e, "transform", "translate3d(" + s + "px," + c + "px,0)"), this.forRepaintDummy = Xr(e), z(e, "transition", "transform " + r + "ms" + (this.options.easing ? " " + this.options.easing : "")), z(e, "transform", "translate3d(0,0,0)"), typeof e.animated == "number" && clearTimeout(e.animated), e.animated = setTimeout(function() {
					z(e, "transition", ""), z(e, "transform", ""), e.animated = !1, e.animatingX = !1, e.animatingY = !1;
				}, r);
			}
		}
	};
}
function Xr(e) {
	return e.offsetWidth;
}
function Zr(e, t, n, r) {
	return Math.sqrt((t.top - e.top) ** 2 + (t.left - e.left) ** 2) / Math.sqrt((t.top - n.top) ** 2 + (t.left - n.left) ** 2) * r.animation;
}
var Qr = [], $r = { initializeByDefault: !0 }, ei = {
	mount: function(e) {
		for (var t in $r) $r.hasOwnProperty(t) && !(t in e) && (e[t] = $r[t]);
		Qr.forEach(function(t) {
			if (t.pluginName === e.pluginName) throw `Sortable: Cannot mount plugin ${e.pluginName} more than once`;
		}), Qr.push(e);
	},
	pluginEvent: function(e, t, n) {
		var r = this;
		this.eventCanceled = !1, n.cancel = function() {
			r.eventCanceled = !0;
		};
		var i = e + "Global";
		Qr.forEach(function(r) {
			t[r.pluginName] && (t[r.pluginName][i] && t[r.pluginName][i](fr({ sortable: t }, n)), t.options[r.pluginName] && t[r.pluginName][e] && t[r.pluginName][e](fr({ sortable: t }, n)));
		});
	},
	initializePlugins: function(e, t, n, r) {
		for (var i in Qr.forEach(function(r) {
			var i = r.pluginName;
			if (!(!e.options[i] && !r.initializeByDefault)) {
				var a = new r(e, t, e.options);
				a.sortable = e, a.options = e.options, e[i] = a, hr(n, a.defaults);
			}
		}), e.options) if (e.options.hasOwnProperty(i)) {
			var a = this.modifyOption(e, i, e.options[i]);
			a !== void 0 && (e.options[i] = a);
		}
	},
	getEventProperties: function(e, t) {
		var n = {};
		return Qr.forEach(function(r) {
			typeof r.eventProperties == "function" && hr(n, r.eventProperties.call(t[r.pluginName], e));
		}), n;
	},
	modifyOption: function(e, t, n) {
		var r;
		return Qr.forEach(function(i) {
			e[i.pluginName] && i.optionListeners && typeof i.optionListeners[t] == "function" && (r = i.optionListeners[t].call(e[i.pluginName], n));
		}), r;
	}
};
function ti(e) {
	var t = e.sortable, n = e.rootEl, r = e.name, i = e.targetEl, a = e.cloneEl, o = e.toEl, s = e.fromEl, c = e.oldIndex, l = e.newIndex, u = e.oldDraggableIndex, d = e.newDraggableIndex, f = e.originalEvent, p = e.putSortable, m = e.extraEventProperties;
	if (t ||= n && n[V], t) {
		var h, g = t.options, _ = "on" + r.charAt(0).toUpperCase() + r.substr(1);
		window.CustomEvent && !br && !xr ? h = new CustomEvent(r, {
			bubbles: !0,
			cancelable: !0
		}) : (h = document.createEvent("Event"), h.initEvent(r, !0, !0)), h.to = o || n, h.from = s || n, h.item = i || n, h.clone = a, h.oldIndex = c, h.newIndex = l, h.oldDraggableIndex = u, h.newDraggableIndex = d, h.originalEvent = f, h.pullMode = p ? p.lastPutMode : void 0;
		var v = fr(fr({}, m), ei.getEventProperties(r, t));
		for (var y in v) h[y] = v[y];
		n && n.dispatchEvent(h), g[_] && g[_].call(t, h);
	}
}
var ni = ["evt"], H = function(e, t) {
	var n = arguments.length > 2 && arguments[2] !== void 0 ? arguments[2] : {}, r = n.evt, i = _r(n, ni);
	ei.pluginEvent.bind(Q)(e, t, fr({
		dragEl: W,
		parentEl: G,
		ghostEl: K,
		rootEl: q,
		nextEl: ri,
		lastDownEl: ii,
		cloneEl: J,
		cloneHidden: ai,
		dragStarted: yi,
		putSortable: X,
		activeSortable: Q.active,
		originalEvent: r,
		oldIndex: oi,
		oldDraggableIndex: si,
		newIndex: Y,
		newDraggableIndex: ci,
		hideGhostForTarget: Li,
		unhideGhostForTarget: Ri,
		cloneNowHidden: function() {
			ai = !0;
		},
		cloneNowShown: function() {
			ai = !1;
		},
		dispatchSortableEvent: function(e) {
			U({
				sortable: t,
				name: e,
				originalEvent: r
			});
		}
	}, i));
};
function U(e) {
	ti(fr({
		putSortable: X,
		cloneEl: J,
		targetEl: W,
		rootEl: q,
		oldIndex: oi,
		oldDraggableIndex: si,
		newIndex: Y,
		newDraggableIndex: ci
	}, e));
}
var W, G, K, q, ri, ii, J, ai, oi, Y, si, ci, li, X, ui = !1, di = !1, fi = [], pi, mi, hi, gi, _i, vi, yi, bi, xi, Si = !1, Ci = !1, wi, Z, Ti = [], Ei = !1, Di = [], Oi = typeof document < "u", ki = wr, Ai = xr || br ? "cssFloat" : "float", ji = Oi && !Tr && !wr && "draggable" in document.createElement("div"), Mi = function() {
	if (Oi) {
		if (br) return !1;
		var e = document.createElement("x");
		return e.style.cssText = "pointer-events:auto", e.style.pointerEvents === "auto";
	}
}(), Ni = function(e, t) {
	var n = z(e), r = parseInt(n.width) - parseInt(n.paddingLeft) - parseInt(n.paddingRight) - parseInt(n.borderLeftWidth) - parseInt(n.borderRightWidth), i = Ir(e, 0, t), a = Ir(e, 1, t), o = i && z(i), s = a && z(a), c = o && parseInt(o.marginLeft) + parseInt(o.marginRight) + B(i).width, l = s && parseInt(s.marginLeft) + parseInt(s.marginRight) + B(a).width;
	if (n.display === "flex") return n.flexDirection === "column" || n.flexDirection === "column-reverse" ? "vertical" : "horizontal";
	if (n.display === "grid") return n.gridTemplateColumns.split(" ").length <= 1 ? "vertical" : "horizontal";
	if (i && o.float && o.float !== "none") {
		var u = o.float === "left" ? "left" : "right";
		return a && (s.clear === "both" || s.clear === u) ? "vertical" : "horizontal";
	}
	return i && (o.display === "block" || o.display === "flex" || o.display === "table" || o.display === "grid" || c >= r && n[Ai] === "none" || a && n[Ai] === "none" && c + l > r) ? "vertical" : "horizontal";
}, Pi = function(e, t, n) {
	var r = n ? e.left : e.top, i = n ? e.right : e.bottom, a = n ? e.width : e.height, o = n ? t.left : t.top, s = n ? t.right : t.bottom, c = n ? t.width : t.height;
	return r === o || i === s || r + a / 2 === o + c / 2;
}, Fi = function(e, t) {
	var n;
	return fi.some(function(r) {
		var i = r[V].options.emptyInsertThreshold;
		if (!(!i || Lr(r))) {
			var a = B(r), o = e >= a.left - i && e <= a.right + i, s = t >= a.top - i && t <= a.bottom + i;
			if (o && s) return n = r;
		}
	}), n;
}, Ii = function(e) {
	function t(e, n) {
		return function(r, i, a, o) {
			var s = r.options.group.name && i.options.group.name && r.options.group.name === i.options.group.name;
			if (e == null && (n || s)) return !0;
			if (e == null || e === !1) return !1;
			if (n && e === "clone") return e;
			if (typeof e == "function") return t(e(r, i, a, o), n)(r, i, a, o);
			var c = (n ? r : i).options.group.name;
			return e === !0 || typeof e == "string" && e === c || e.join && e.indexOf(c) > -1;
		};
	}
	var n = {}, r = e.group;
	(!r || pr(r) != "object") && (r = { name: r }), n.name = r.name, n.checkPull = t(r.pull, !0), n.checkPut = t(r.put), n.revertClone = r.revertClone, e.group = n;
}, Li = function() {
	!Mi && K && z(K, "display", "none");
}, Ri = function() {
	!Mi && K && z(K, "display", "");
};
Oi && document.addEventListener("click", function(e) {
	if (di) return e.preventDefault(), e.stopPropagation && e.stopPropagation(), e.stopImmediatePropagation && e.stopImmediatePropagation(), di = !1, !1;
}, !0);
var zi = function(e) {
	if (W) {
		e = e.touches ? e.touches[0] : e;
		var t = Fi(e.clientX, e.clientY);
		if (t) {
			var n = {};
			for (var r in e) e.hasOwnProperty(r) && (n[r] = e[r]);
			n.target = n.rootEl = t, n.preventDefault = void 0, n.stopPropagation = void 0, t[V]._onDragOver(n);
		}
	}
}, Bi = function(e) {
	W && W.parentNode[V]._isOutsideThisEl(e.target);
};
function Q(e, t) {
	if (!(e && e.nodeType && e.nodeType === 1)) throw `Sortable: \`el\` must be an HTMLElement, not ${{}.toString.call(e)}`;
	this.el = e, this.options = t = hr({}, t), e[V] = this;
	var n = {
		group: null,
		sort: !0,
		disabled: !1,
		store: null,
		handle: null,
		draggable: /^[uo]l$/i.test(e.nodeName) ? ">li" : ">*",
		swapThreshold: 1,
		invertSwap: !1,
		invertedSwapThreshold: null,
		removeCloneOnHide: !0,
		direction: function() {
			return Ni(e, this.options);
		},
		ghostClass: "sortable-ghost",
		chosenClass: "sortable-chosen",
		dragClass: "sortable-drag",
		ignore: "a, img",
		filter: null,
		preventOnFilter: !0,
		animation: 0,
		easing: null,
		setData: function(e, t) {
			e.setData("Text", t.textContent);
		},
		dropBubble: !1,
		dragoverBubble: !1,
		dataIdAttr: "data-id",
		delay: 0,
		delayOnTouchOnly: !1,
		touchStartThreshold: (Number.parseInt ? Number : window).parseInt(window.devicePixelRatio, 10) || 1,
		forceFallback: !1,
		fallbackClass: "sortable-fallback",
		fallbackOnBody: !1,
		fallbackTolerance: 0,
		fallbackOffset: {
			x: 0,
			y: 0
		},
		supportPointer: Q.supportPointer !== !1 && "PointerEvent" in window && !Cr,
		emptyInsertThreshold: 5
	};
	for (var r in ei.initializePlugins(this, e, n), n) !(r in t) && (t[r] = n[r]);
	for (var i in Ii(t), this) i.charAt(0) === "_" && typeof this[i] == "function" && (this[i] = this[i].bind(this));
	this.nativeDraggable = t.forceFallback ? !1 : ji, this.nativeDraggable && (this.options.touchStartThreshold = 1), t.supportPointer ? L(e, "pointerdown", this._onTapStart) : (L(e, "mousedown", this._onTapStart), L(e, "touchstart", this._onTapStart)), this.nativeDraggable && (L(e, "dragover", this), L(e, "dragenter", this)), fi.push(this.el), t.store && t.store.get && this.sort(t.store.get(this) || []), hr(this, Yr());
}
Q.prototype = {
	constructor: Q,
	_isOutsideThisEl: function(e) {
		!this.el.contains(e) && e !== this.el && (bi = null);
	},
	_getDirection: function(e, t) {
		return typeof this.options.direction == "function" ? this.options.direction.call(this, e, t, W) : this.options.direction;
	},
	_onTapStart: function(e) {
		if (e.cancelable) {
			var t = this, n = this.el, r = this.options, i = r.preventOnFilter, a = e.type, o = e.touches && e.touches[0] || e.pointerType && e.pointerType === "touch" && e, s = (o || e).target, c = e.target.shadowRoot && (e.path && e.path[0] || e.composedPath && e.composedPath()[0]) || s, l = r.filter;
			if (Xi(n), !W && !(/mousedown|pointerdown/.test(a) && e.button !== 0 || r.disabled) && !c.isContentEditable && !(!this.nativeDraggable && Cr && s && s.tagName.toUpperCase() === "SELECT") && (s = kr(s, r.draggable, n, !1), !(s && s.animated) && ii !== s)) {
				if (oi = Rr(s), si = Rr(s, r.draggable), typeof l == "function") {
					if (l.call(this, e, s, this)) {
						U({
							sortable: t,
							rootEl: c,
							name: "filter",
							targetEl: s,
							toEl: n,
							fromEl: n
						}), H("filter", t, { evt: e }), i && e.cancelable && e.preventDefault();
						return;
					}
				} else if (l && (l = l.split(",").some(function(r) {
					if (r = kr(c, r.trim(), n, !1), r) return U({
						sortable: t,
						rootEl: r,
						name: "filter",
						targetEl: s,
						fromEl: n,
						toEl: n
					}), H("filter", t, { evt: e }), !0;
				}), l)) {
					i && e.cancelable && e.preventDefault();
					return;
				}
				r.handle && !kr(c, r.handle, n, !1) || this._prepareDragStart(e, o, s);
			}
		}
	},
	_prepareDragStart: function(e, t, n) {
		var r = this, i = r.el, a = r.options, o = i.ownerDocument, s;
		if (n && !W && n.parentNode === i) {
			var c = B(n);
			if (q = i, W = n, G = W.parentNode, ri = W.nextSibling, ii = n, li = a.group, Q.dragged = W, pi = {
				target: W,
				clientX: (t || e).clientX,
				clientY: (t || e).clientY
			}, _i = pi.clientX - c.left, vi = pi.clientY - c.top, this._lastX = (t || e).clientX, this._lastY = (t || e).clientY, W.style["will-change"] = "all", s = function() {
				if (H("delayEnded", r, { evt: e }), Q.eventCanceled) {
					r._onDrop();
					return;
				}
				r._disableDelayedDragEvents(), !Sr && r.nativeDraggable && (W.draggable = !0), r._triggerDragStart(e, t), U({
					sortable: r,
					name: "choose",
					originalEvent: e
				}), jr(W, a.chosenClass, !0);
			}, a.ignore.split(",").forEach(function(e) {
				Nr(W, e.trim(), Ui);
			}), L(o, "dragover", zi), L(o, "mousemove", zi), L(o, "touchmove", zi), L(o, "mouseup", r._onDrop), L(o, "touchend", r._onDrop), L(o, "touchcancel", r._onDrop), Sr && this.nativeDraggable && (this.options.touchStartThreshold = 4, W.draggable = !0), H("delayStart", this, { evt: e }), a.delay && (!a.delayOnTouchOnly || t) && (!this.nativeDraggable || !(xr || br))) {
				if (Q.eventCanceled) {
					this._onDrop();
					return;
				}
				L(o, "mouseup", r._disableDelayedDrag), L(o, "touchend", r._disableDelayedDrag), L(o, "touchcancel", r._disableDelayedDrag), L(o, "mousemove", r._delayedDragTouchMoveHandler), L(o, "touchmove", r._delayedDragTouchMoveHandler), a.supportPointer && L(o, "pointermove", r._delayedDragTouchMoveHandler), r._dragStartTimer = setTimeout(s, a.delay);
			} else s();
		}
	},
	_delayedDragTouchMoveHandler: function(e) {
		var t = e.touches ? e.touches[0] : e;
		Math.max(Math.abs(t.clientX - this._lastX), Math.abs(t.clientY - this._lastY)) >= Math.floor(this.options.touchStartThreshold / (this.nativeDraggable && window.devicePixelRatio || 1)) && this._disableDelayedDrag();
	},
	_disableDelayedDrag: function() {
		W && Ui(W), clearTimeout(this._dragStartTimer), this._disableDelayedDragEvents();
	},
	_disableDelayedDragEvents: function() {
		var e = this.el.ownerDocument;
		R(e, "mouseup", this._disableDelayedDrag), R(e, "touchend", this._disableDelayedDrag), R(e, "touchcancel", this._disableDelayedDrag), R(e, "mousemove", this._delayedDragTouchMoveHandler), R(e, "touchmove", this._delayedDragTouchMoveHandler), R(e, "pointermove", this._delayedDragTouchMoveHandler);
	},
	_triggerDragStart: function(e, t) {
		t ||= e.pointerType == "touch" && e, !this.nativeDraggable || t ? this.options.supportPointer ? L(document, "pointermove", this._onTouchMove) : t ? L(document, "touchmove", this._onTouchMove) : L(document, "mousemove", this._onTouchMove) : (L(W, "dragend", this), L(q, "dragstart", this._onDragStart));
		try {
			document.selection ? Zi(function() {
				document.selection.empty();
			}) : window.getSelection().removeAllRanges();
		} catch {}
	},
	_dragStarted: function(e, t) {
		if (ui = !1, q && W) {
			H("dragStarted", this, { evt: t }), this.nativeDraggable && L(document, "dragover", Bi);
			var n = this.options;
			!e && jr(W, n.dragClass, !1), jr(W, n.ghostClass, !0), Q.active = this, e && this._appendGhost(), U({
				sortable: this,
				name: "start",
				originalEvent: t
			});
		} else this._nulling();
	},
	_emulateDragOver: function() {
		if (mi) {
			this._lastX = mi.clientX, this._lastY = mi.clientY, Li();
			for (var e = document.elementFromPoint(mi.clientX, mi.clientY), t = e; e && e.shadowRoot && (e = e.shadowRoot.elementFromPoint(mi.clientX, mi.clientY), e !== t);) t = e;
			if (W.parentNode[V]._isOutsideThisEl(e), t) do {
				if (t[V]) {
					var n = void 0;
					if (n = t[V]._onDragOver({
						clientX: mi.clientX,
						clientY: mi.clientY,
						target: e,
						rootEl: t
					}), n && !this.options.dragoverBubble) break;
				}
				e = t;
			} while (t = t.parentNode);
			Ri();
		}
	},
	_onTouchMove: function(e) {
		if (pi) {
			var t = this.options, n = t.fallbackTolerance, r = t.fallbackOffset, i = e.touches ? e.touches[0] : e, a = K && Mr(K, !0), o = K && a && a.a, s = K && a && a.d, c = ki && Z && zr(Z), l = (i.clientX - pi.clientX + r.x) / (o || 1) + (c ? c[0] - Ti[0] : 0) / (o || 1), u = (i.clientY - pi.clientY + r.y) / (s || 1) + (c ? c[1] - Ti[1] : 0) / (s || 1);
			if (!Q.active && !ui) {
				if (n && Math.max(Math.abs(i.clientX - this._lastX), Math.abs(i.clientY - this._lastY)) < n) return;
				this._onDragStart(e, !0);
			}
			if (K) {
				a ? (a.e += l - (hi || 0), a.f += u - (gi || 0)) : a = {
					a: 1,
					b: 0,
					c: 0,
					d: 1,
					e: l,
					f: u
				};
				var d = `matrix(${a.a},${a.b},${a.c},${a.d},${a.e},${a.f})`;
				z(K, "webkitTransform", d), z(K, "mozTransform", d), z(K, "msTransform", d), z(K, "transform", d), hi = l, gi = u, mi = i;
			}
			e.cancelable && e.preventDefault();
		}
	},
	_appendGhost: function() {
		if (!K) {
			var e = this.options.fallbackOnBody ? document.body : q, t = B(W, !0, ki, !0, e), n = this.options;
			if (ki) {
				for (Z = e; z(Z, "position") === "static" && z(Z, "transform") === "none" && Z !== document;) Z = Z.parentNode;
				Z !== document.body && Z !== document.documentElement ? (Z === document && (Z = Pr()), t.top += Z.scrollTop, t.left += Z.scrollLeft) : Z = Pr(), Ti = zr(Z);
			}
			K = W.cloneNode(!0), jr(K, n.ghostClass, !1), jr(K, n.fallbackClass, !0), jr(K, n.dragClass, !0), z(K, "transition", ""), z(K, "transform", ""), z(K, "box-sizing", "border-box"), z(K, "margin", 0), z(K, "top", t.top), z(K, "left", t.left), z(K, "width", t.width), z(K, "height", t.height), z(K, "opacity", "0.8"), z(K, "position", ki ? "absolute" : "fixed"), z(K, "zIndex", "100000"), z(K, "pointerEvents", "none"), Q.ghost = K, e.appendChild(K), z(K, "transform-origin", _i / parseInt(K.style.width) * 100 + "% " + vi / parseInt(K.style.height) * 100 + "%");
		}
	},
	_onDragStart: function(e, t) {
		var n = this, r = e.dataTransfer, i = n.options;
		if (H("dragStart", this, { evt: e }), Q.eventCanceled) {
			this._onDrop();
			return;
		}
		H("setupClone", this), Q.eventCanceled || (J = Jr(W), J.draggable = !1, J.style["will-change"] = "", this._hideClone(), jr(J, this.options.chosenClass, !1), Q.clone = J), n.cloneId = Zi(function() {
			H("clone", n), !Q.eventCanceled && (n.options.removeCloneOnHide || q.insertBefore(J, W), n._hideClone(), U({
				sortable: n,
				name: "clone"
			}));
		}), !t && jr(W, i.dragClass, !0), t ? (di = !0, n._loopId = setInterval(n._emulateDragOver, 50)) : (R(document, "mouseup", n._onDrop), R(document, "touchend", n._onDrop), R(document, "touchcancel", n._onDrop), r && (r.effectAllowed = "move", i.setData && i.setData.call(n, r, W)), L(document, "drop", n), z(W, "transform", "translateZ(0)")), ui = !0, n._dragStartId = Zi(n._dragStarted.bind(n, t, e)), L(document, "selectstart", n), yi = !0, Cr && z(document.body, "user-select", "none");
	},
	_onDragOver: function(e) {
		var t = this.el, n = e.target, r, i, a, o = this.options, s = o.group, c = Q.active, l = li === s, u = o.sort, d = X || c, f, p = this, m = !1;
		if (Ei) return;
		function h(o, s) {
			H(o, p, fr({
				evt: e,
				isOwner: l,
				axis: f ? "vertical" : "horizontal",
				revert: a,
				dragRect: r,
				targetRect: i,
				canSort: u,
				fromSortable: d,
				target: n,
				completed: _,
				onMove: function(n, i) {
					return Hi(q, t, W, r, n, B(n), e, i);
				},
				changed: v
			}, s));
		}
		function g() {
			h("dragOverAnimationCapture"), p.captureAnimationState(), p !== d && d.captureAnimationState();
		}
		function _(r) {
			return h("dragOverCompleted", { insertion: r }), r && (l ? c._hideClone() : c._showClone(p), p !== d && (jr(W, X ? X.options.ghostClass : c.options.ghostClass, !1), jr(W, o.ghostClass, !0)), X !== p && p !== Q.active ? X = p : p === Q.active && X && (X = null), d === p && (p._ignoreWhileAnimating = n), p.animateAll(function() {
				h("dragOverAnimationComplete"), p._ignoreWhileAnimating = null;
			}), p !== d && (d.animateAll(), d._ignoreWhileAnimating = null)), (n === W && !W.animated || n === t && !n.animated) && (bi = null), !o.dragoverBubble && !e.rootEl && n !== document && (W.parentNode[V]._isOutsideThisEl(e.target), !r && zi(e)), !o.dragoverBubble && e.stopPropagation && e.stopPropagation(), m = !0;
		}
		function v() {
			Y = Rr(W), ci = Rr(W, o.draggable), U({
				sortable: p,
				name: "change",
				toEl: t,
				newIndex: Y,
				newDraggableIndex: ci,
				originalEvent: e
			});
		}
		if (e.preventDefault !== void 0 && e.cancelable && e.preventDefault(), n = kr(n, o.draggable, t, !0), h("dragOver"), Q.eventCanceled) return m;
		if (W.contains(e.target) || n.animated && n.animatingX && n.animatingY || p._ignoreWhileAnimating === n) return _(!1);
		if (di = !1, c && !o.disabled && (l ? u || (a = G !== q) : X === this || (this.lastPutMode = li.checkPull(this, c, W, e)) && s.checkPut(this, c, W, e))) {
			if (f = this._getDirection(e, n) === "vertical", r = B(W), h("dragOverValid"), Q.eventCanceled) return m;
			if (a) return G = q, g(), this._hideClone(), h("revert"), Q.eventCanceled || (ri ? q.insertBefore(W, ri) : q.appendChild(W)), _(!0);
			var y = Lr(t, o.draggable);
			if (!y || Ki(e, f, this) && !y.animated) {
				if (y === W) return _(!1);
				if (y && t === e.target && (n = y), n && (i = B(n)), Hi(q, t, W, r, n, i, e, !!n) !== !1) return g(), t.appendChild(W), G = t, v(), _(!0);
			} else if (y && Gi(e, f, this)) {
				var b = Ir(t, 0, o, !0);
				if (b === W) return _(!1);
				if (n = b, i = B(n), Hi(q, t, W, r, n, i, e, !1) !== !1) return g(), t.insertBefore(W, b), G = t, v(), _(!0);
			} else if (n.parentNode === t) {
				i = B(n);
				var x = 0, S, C = W.parentNode !== t, w = !Pi(W.animated && W.toRect || r, n.animated && n.toRect || i, f), T = f ? "top" : "left", E = Fr(n, "top", "top") || Fr(W, "top", "top"), D = E ? E.scrollTop : void 0;
				bi !== n && (S = i[T], Si = !1, Ci = !w && o.invertSwap || C), x = qi(e, n, i, f, w ? 1 : o.swapThreshold, o.invertedSwapThreshold == null ? o.swapThreshold : o.invertedSwapThreshold, Ci, bi === n);
				var O;
				if (x !== 0) {
					var k = Rr(W);
					do
						k -= x, O = G.children[k];
					while (O && (z(O, "display") === "none" || O === K));
				}
				if (x === 0 || O === n) return _(!1);
				bi = n, xi = x;
				var A = n.nextElementSibling, j = !1;
				j = x === 1;
				var M = Hi(q, t, W, r, n, i, e, j);
				if (M !== !1) return (M === 1 || M === -1) && (j = M === 1), Ei = !0, setTimeout(Wi, 30), g(), j && !A ? t.appendChild(W) : n.parentNode.insertBefore(W, j ? A : n), E && qr(E, 0, D - E.scrollTop), G = W.parentNode, S !== void 0 && !Ci && (wi = Math.abs(S - B(n)[T])), v(), _(!0);
			}
			if (t.contains(W)) return _(!1);
		}
		return !1;
	},
	_ignoreWhileAnimating: null,
	_offMoveEvents: function() {
		R(document, "mousemove", this._onTouchMove), R(document, "touchmove", this._onTouchMove), R(document, "pointermove", this._onTouchMove), R(document, "dragover", zi), R(document, "mousemove", zi), R(document, "touchmove", zi);
	},
	_offUpEvents: function() {
		var e = this.el.ownerDocument;
		R(e, "mouseup", this._onDrop), R(e, "touchend", this._onDrop), R(e, "pointerup", this._onDrop), R(e, "touchcancel", this._onDrop), R(document, "selectstart", this);
	},
	_onDrop: function(e) {
		var t = this.el, n = this.options;
		if (Y = Rr(W), ci = Rr(W, n.draggable), H("drop", this, { evt: e }), G = W && W.parentNode, Y = Rr(W), ci = Rr(W, n.draggable), Q.eventCanceled) {
			this._nulling();
			return;
		}
		ui = !1, Ci = !1, Si = !1, clearInterval(this._loopId), clearTimeout(this._dragStartTimer), Qi(this.cloneId), Qi(this._dragStartId), this.nativeDraggable && (R(document, "drop", this), R(t, "dragstart", this._onDragStart)), this._offMoveEvents(), this._offUpEvents(), Cr && z(document.body, "user-select", ""), z(W, "transform", ""), e && (yi && (e.cancelable && e.preventDefault(), !n.dropBubble && e.stopPropagation()), K && K.parentNode && K.parentNode.removeChild(K), (q === G || X && X.lastPutMode !== "clone") && J && J.parentNode && J.parentNode.removeChild(J), W && (this.nativeDraggable && R(W, "dragend", this), Ui(W), W.style["will-change"] = "", yi && !ui && jr(W, X ? X.options.ghostClass : this.options.ghostClass, !1), jr(W, this.options.chosenClass, !1), U({
			sortable: this,
			name: "unchoose",
			toEl: G,
			newIndex: null,
			newDraggableIndex: null,
			originalEvent: e
		}), q === G ? Y !== oi && Y >= 0 && (U({
			sortable: this,
			name: "update",
			toEl: G,
			originalEvent: e
		}), U({
			sortable: this,
			name: "sort",
			toEl: G,
			originalEvent: e
		})) : (Y >= 0 && (U({
			rootEl: G,
			name: "add",
			toEl: G,
			fromEl: q,
			originalEvent: e
		}), U({
			sortable: this,
			name: "remove",
			toEl: G,
			originalEvent: e
		}), U({
			rootEl: G,
			name: "sort",
			toEl: G,
			fromEl: q,
			originalEvent: e
		}), U({
			sortable: this,
			name: "sort",
			toEl: G,
			originalEvent: e
		})), X && X.save()), Q.active && ((Y == null || Y === -1) && (Y = oi, ci = si), U({
			sortable: this,
			name: "end",
			toEl: G,
			originalEvent: e
		}), this.save()))), this._nulling();
	},
	_nulling: function() {
		H("nulling", this), q = W = G = K = ri = J = ii = ai = pi = mi = yi = Y = ci = oi = si = bi = xi = X = li = Q.dragged = Q.ghost = Q.clone = Q.active = null, Di.forEach(function(e) {
			e.checked = !0;
		}), Di.length = hi = gi = 0;
	},
	handleEvent: function(e) {
		switch (e.type) {
			case "drop":
			case "dragend":
				this._onDrop(e);
				break;
			case "dragenter":
			case "dragover":
				W && (this._onDragOver(e), Vi(e));
				break;
			case "selectstart":
				e.preventDefault();
				break;
		}
	},
	toArray: function() {
		for (var e = [], t, n = this.el.children, r = 0, i = n.length, a = this.options; r < i; r++) t = n[r], kr(t, a.draggable, this.el, !1) && e.push(t.getAttribute(a.dataIdAttr) || Yi(t));
		return e;
	},
	sort: function(e, t) {
		var n = {}, r = this.el;
		this.toArray().forEach(function(e, t) {
			var i = r.children[t];
			kr(i, this.options.draggable, r, !1) && (n[e] = i);
		}, this), t && this.captureAnimationState(), e.forEach(function(e) {
			n[e] && (r.removeChild(n[e]), r.appendChild(n[e]));
		}), t && this.animateAll();
	},
	save: function() {
		var e = this.options.store;
		e && e.set && e.set(this);
	},
	closest: function(e, t) {
		return kr(e, t || this.options.draggable, this.el, !1);
	},
	option: function(e, t) {
		var n = this.options;
		if (t === void 0) return n[e];
		var r = ei.modifyOption(this, e, t);
		r === void 0 ? n[e] = t : n[e] = r, e === "group" && Ii(n);
	},
	destroy: function() {
		H("destroy", this);
		var e = this.el;
		e[V] = null, R(e, "mousedown", this._onTapStart), R(e, "touchstart", this._onTapStart), R(e, "pointerdown", this._onTapStart), this.nativeDraggable && (R(e, "dragover", this), R(e, "dragenter", this)), Array.prototype.forEach.call(e.querySelectorAll("[draggable]"), function(e) {
			e.removeAttribute("draggable");
		}), this._onDrop(), this._disableDelayedDragEvents(), fi.splice(fi.indexOf(this.el), 1), this.el = e = null;
	},
	_hideClone: function() {
		if (!ai) {
			if (H("hideClone", this), Q.eventCanceled) return;
			z(J, "display", "none"), this.options.removeCloneOnHide && J.parentNode && J.parentNode.removeChild(J), ai = !0;
		}
	},
	_showClone: function(e) {
		if (e.lastPutMode !== "clone") {
			this._hideClone();
			return;
		}
		if (ai) {
			if (H("showClone", this), Q.eventCanceled) return;
			W.parentNode == q && !this.options.group.revertClone ? q.insertBefore(J, W) : ri ? q.insertBefore(J, ri) : q.appendChild(J), this.options.group.revertClone && this.animate(W, J), z(J, "display", ""), ai = !1;
		}
	}
};
function Vi(e) {
	e.dataTransfer && (e.dataTransfer.dropEffect = "move"), e.cancelable && e.preventDefault();
}
function Hi(e, t, n, r, i, a, o, s) {
	var c, l = e[V], u = l.options.onMove, d;
	return window.CustomEvent && !br && !xr ? c = new CustomEvent("move", {
		bubbles: !0,
		cancelable: !0
	}) : (c = document.createEvent("Event"), c.initEvent("move", !0, !0)), c.to = t, c.from = e, c.dragged = n, c.draggedRect = r, c.related = i || t, c.relatedRect = a || B(t), c.willInsertAfter = s, c.originalEvent = o, e.dispatchEvent(c), u && (d = u.call(l, c, o)), d;
}
function Ui(e) {
	e.draggable = !1;
}
function Wi() {
	Ei = !1;
}
function Gi(e, t, n) {
	var r = B(Ir(n.el, 0, n.options, !0)), i = 10;
	return t ? e.clientX < r.left - i || e.clientY < r.top && e.clientX < r.right : e.clientY < r.top - i || e.clientY < r.bottom && e.clientX < r.left;
}
function Ki(e, t, n) {
	var r = B(Lr(n.el, n.options.draggable)), i = 10;
	return t ? e.clientX > r.right + i || e.clientX <= r.right && e.clientY > r.bottom && e.clientX >= r.left : e.clientX > r.right && e.clientY > r.top || e.clientX <= r.right && e.clientY > r.bottom + i;
}
function qi(e, t, n, r, i, a, o, s) {
	var c = r ? e.clientY : e.clientX, l = r ? n.height : n.width, u = r ? n.top : n.left, d = r ? n.bottom : n.right, f = !1;
	if (!o) {
		if (s && wi < l * i) {
			if (!Si && (xi === 1 ? c > u + l * a / 2 : c < d - l * a / 2) && (Si = !0), Si) f = !0;
			else if (xi === 1 ? c < u + wi : c > d - wi) return -xi;
		} else if (c > u + l * (1 - i) / 2 && c < d - l * (1 - i) / 2) return Ji(t);
	}
	return f ||= o, f && (c < u + l * a / 2 || c > d - l * a / 2) ? c > u + l / 2 ? 1 : -1 : 0;
}
function Ji(e) {
	return Rr(W) < Rr(e) ? 1 : -1;
}
function Yi(e) {
	for (var t = e.tagName + e.className + e.src + e.href + e.textContent, n = t.length, r = 0; n--;) r += t.charCodeAt(n);
	return r.toString(36);
}
function Xi(e) {
	Di.length = 0;
	for (var t = e.getElementsByTagName("input"), n = t.length; n--;) {
		var r = t[n];
		r.checked && Di.push(r);
	}
}
function Zi(e) {
	return setTimeout(e, 0);
}
function Qi(e) {
	return clearTimeout(e);
}
Oi && L(document, "touchmove", function(e) {
	(Q.active || ui) && e.cancelable && e.preventDefault();
}), Q.utils = {
	on: L,
	off: R,
	css: z,
	find: Nr,
	is: function(e, t) {
		return !!kr(e, t, e, !1);
	},
	extend: Hr,
	throttle: Gr,
	closest: kr,
	toggleClass: jr,
	clone: Jr,
	index: Rr,
	nextTick: Zi,
	cancelNextTick: Qi,
	detectDirection: Ni,
	getChild: Ir
}, Q.get = function(e) {
	return e[V];
}, Q.mount = function() {
	var e = [...arguments];
	e[0].constructor === Array && (e = e[0]), e.forEach(function(e) {
		if (!e.prototype || !e.prototype.constructor) throw `Sortable: Mounted plugin must be a constructor function, not ${{}.toString.call(e)}`;
		e.utils && (Q.utils = fr(fr({}, Q.utils), e.utils)), ei.mount(e);
	});
}, Q.create = function(e, t) {
	return new Q(e, t);
}, Q.version = vr;
var $ = [], $i, ea, ta = !1, na, ra, ia, aa;
function oa() {
	function e() {
		for (var e in this.defaults = {
			scroll: !0,
			forceAutoScrollFallback: !1,
			scrollSensitivity: 30,
			scrollSpeed: 10,
			bubbleScroll: !0
		}, this) e.charAt(0) === "_" && typeof this[e] == "function" && (this[e] = this[e].bind(this));
	}
	return e.prototype = {
		dragStarted: function(e) {
			var t = e.originalEvent;
			this.sortable.nativeDraggable ? L(document, "dragover", this._handleAutoScroll) : this.options.supportPointer ? L(document, "pointermove", this._handleFallbackAutoScroll) : t.touches ? L(document, "touchmove", this._handleFallbackAutoScroll) : L(document, "mousemove", this._handleFallbackAutoScroll);
		},
		dragOverCompleted: function(e) {
			var t = e.originalEvent;
			!this.options.dragOverBubble && !t.rootEl && this._handleAutoScroll(t);
		},
		drop: function() {
			this.sortable.nativeDraggable ? R(document, "dragover", this._handleAutoScroll) : (R(document, "pointermove", this._handleFallbackAutoScroll), R(document, "touchmove", this._handleFallbackAutoScroll), R(document, "mousemove", this._handleFallbackAutoScroll)), ca(), sa(), Kr();
		},
		nulling: function() {
			ia = ea = $i = ta = aa = na = ra = null, $.length = 0;
		},
		_handleFallbackAutoScroll: function(e) {
			this._handleAutoScroll(e, !0);
		},
		_handleAutoScroll: function(e, t) {
			var n = this, r = (e.touches ? e.touches[0] : e).clientX, i = (e.touches ? e.touches[0] : e).clientY, a = document.elementFromPoint(r, i);
			if (ia = e, t || this.options.forceAutoScrollFallback || xr || br || Cr) {
				la(e, this.options, a, t);
				var o = Vr(a, !0);
				ta && (!aa || r !== na || i !== ra) && (aa && ca(), aa = setInterval(function() {
					var a = Vr(document.elementFromPoint(r, i), !0);
					a !== o && (o = a, sa()), la(e, n.options, a, t);
				}, 10), na = r, ra = i);
			} else {
				if (!this.options.bubbleScroll || Vr(a, !0) === Pr()) {
					sa();
					return;
				}
				la(e, this.options, Vr(a, !1), !1);
			}
		}
	}, hr(e, {
		pluginName: "scroll",
		initializeByDefault: !0
	});
}
function sa() {
	$.forEach(function(e) {
		clearInterval(e.pid);
	}), $ = [];
}
function ca() {
	clearInterval(aa);
}
var la = Gr(function(e, t, n, r) {
	if (t.scroll) {
		var i = (e.touches ? e.touches[0] : e).clientX, a = (e.touches ? e.touches[0] : e).clientY, o = t.scrollSensitivity, s = t.scrollSpeed, c = Pr(), l = !1, u;
		ea !== n && (ea = n, sa(), $i = t.scroll, u = t.scrollFn, $i === !0 && ($i = Vr(n, !0)));
		var d = 0, f = $i;
		do {
			var p = f, m = B(p), h = m.top, g = m.bottom, _ = m.left, v = m.right, y = m.width, b = m.height, x = void 0, S = void 0, C = p.scrollWidth, w = p.scrollHeight, T = z(p), E = p.scrollLeft, D = p.scrollTop;
			p === c ? (x = y < C && (T.overflowX === "auto" || T.overflowX === "scroll" || T.overflowX === "visible"), S = b < w && (T.overflowY === "auto" || T.overflowY === "scroll" || T.overflowY === "visible")) : (x = y < C && (T.overflowX === "auto" || T.overflowX === "scroll"), S = b < w && (T.overflowY === "auto" || T.overflowY === "scroll"));
			var O = x && (Math.abs(v - i) <= o && E + y < C) - (Math.abs(_ - i) <= o && !!E), k = S && (Math.abs(g - a) <= o && D + b < w) - (Math.abs(h - a) <= o && !!D);
			if (!$[d]) for (var A = 0; A <= d; A++) $[A] || ($[A] = {});
			($[d].vx != O || $[d].vy != k || $[d].el !== p) && ($[d].el = p, $[d].vx = O, $[d].vy = k, clearInterval($[d].pid), (O != 0 || k != 0) && (l = !0, $[d].pid = setInterval(function() {
				r && this.layer === 0 && Q.active._onTouchMove(ia);
				var t = $[this.layer].vy ? $[this.layer].vy * s : 0, n = $[this.layer].vx ? $[this.layer].vx * s : 0;
				typeof u == "function" && u.call(Q.dragged.parentNode[V], n, t, e, ia, $[this.layer].el) !== "continue" || qr($[this.layer].el, n, t);
			}.bind({ layer: d }), 24))), d++;
		} while (t.bubbleScroll && f !== c && (f = Vr(f, !1)));
		ta = l;
	}
}, 30), ua = function(e) {
	var t = e.originalEvent, n = e.putSortable, r = e.dragEl, i = e.activeSortable, a = e.dispatchSortableEvent, o = e.hideGhostForTarget, s = e.unhideGhostForTarget;
	if (t) {
		var c = n || i;
		o();
		var l = t.changedTouches && t.changedTouches.length ? t.changedTouches[0] : t, u = document.elementFromPoint(l.clientX, l.clientY);
		s(), c && !c.el.contains(u) && (a("spill"), this.onSpill({
			dragEl: r,
			putSortable: n
		}));
	}
};
function da() {}
da.prototype = {
	startIndex: null,
	dragStart: function(e) {
		this.startIndex = e.oldDraggableIndex;
	},
	onSpill: function(e) {
		var t = e.dragEl, n = e.putSortable;
		this.sortable.captureAnimationState(), n && n.captureAnimationState();
		var r = Ir(this.sortable.el, this.startIndex, this.options);
		r ? this.sortable.el.insertBefore(t, r) : this.sortable.el.appendChild(t), this.sortable.animateAll(), n && n.animateAll();
	},
	drop: ua
}, hr(da, { pluginName: "revertOnSpill" });
function fa() {}
fa.prototype = {
	onSpill: function(e) {
		var t = e.dragEl, n = e.putSortable || this.sortable;
		n.captureAnimationState(), t.parentNode && t.parentNode.removeChild(t), n.animateAll();
	},
	drop: ua
}, hr(fa, { pluginName: "removeOnSpill" }), Q.mount(new oa()), Q.mount(fa, da);
//#endregion
//#region resources/js/support/filters.ts
var pa = {
	customer: "",
	status: "",
	search: "",
	unbilled: ""
};
function ma(e) {
	return {
		customer: Sa(e.customer),
		status: Sa(e.status),
		search: xa(e.search).slice(0, 200),
		unbilled: e.unbilled === "1" ? "1" : ""
	};
}
function ha(e) {
	let t = {};
	for (let n of [
		"customer",
		"status",
		"search",
		"unbilled"
	]) e[n] !== "" && (t[n] = e[n]);
	return t;
}
function ga(e) {
	return e.customer !== "" || e.status !== "" || e.search !== "" || e.unbilled !== "";
}
function _a(e) {
	return [
		e.customer,
		e.status,
		e.search,
		e.unbilled
	].join("|");
}
function va(e, t) {
	return e.customer === t.customer && e.status === t.status && e.search === t.search && e.unbilled === t.unbilled;
}
function ya(e) {
	let t = Number(e);
	return e !== "" && Number.isInteger(t) && t > 0 ? t : null;
}
function ba(e) {
	let t = {}, n = ya(e.customer);
	n !== null && (t.customer_id = n);
	let r = ya(e.status);
	return r !== null && (t.status_id = r), e.search !== "" && (t.search = e.search), e.unbilled === "1" && (t.unbilled_only = !0), t;
}
function xa(e) {
	let t = Array.isArray(e) ? e[0] : e;
	return typeof t == "string" ? t.trim() : "";
}
function Sa(e) {
	let t = xa(e);
	return ya(t) === null ? "" : t;
}
//#endregion
//#region resources/js/components/TripCard.vue?vue&type=script&setup=true&lang.ts
var Ca = { class: "flex items-start justify-between gap-2" }, wa = { class: "text-sm font-medium text-heading" }, Ta = { class: "relative" }, Ea = {
	key: 0,
	class: "absolute end-0 z-20 mt-1 w-44 rounded-lg border border-line-default bg-surface p-1 shadow-lg"
}, Da = ["onClick"], Oa = { class: "mt-1 text-sm text-muted" }, ka = {
	key: 0,
	class: "mt-1 text-xs text-muted"
}, Aa = { class: "mt-2 flex items-center justify-between gap-2" }, ja = { class: "text-xs text-muted" }, Ma = /* @__PURE__ */ c({
	__name: "TripCard",
	props: {
		trip: {},
		statuses: {}
	},
	emits: [
		"open",
		"move",
		"cancel"
	],
	setup(n, { emit: o }) {
		let s = n, c = o, l = _(!1), u = t(() => `${s.trip.from_city ?? "—"} → ${s.trip.to_city ?? "—"}`), f = t(() => {
			let e = s.trip.customers.map((e) => e.name).filter(Boolean);
			return e.length === 0 ? "No customer" : e.length === 1 ? e[0] : `${e[0]} +${e.length - 1} more`;
		}), p = t(() => s.trip.profit >= 0 ? "text-status-green" : "text-status-red");
		function m() {
			l.value = !l.value;
		}
		function h(e) {
			l.value = !1, c("move", s.trip, e);
		}
		function y() {
			l.value = !1, c("cancel", s.trip);
		}
		return (t, o) => (g(), i("article", {
			class: d(["cursor-pointer rounded-lg border border-line-default bg-surface p-3 shadow-sm hover:bg-hover", { "opacity-50": n.trip.cancelled_at }]),
			onClick: o[1] ||= (e) => c("open", n.trip)
		}, [
			a("div", Ca, [a("p", wa, " #" + b(n.trip.trip_no) + " · " + b(u.value), 1), a("div", Ta, [a("button", {
				type: "button",
				class: "rounded px-1 text-muted hover:text-heading",
				"aria-label": "Trip menu",
				onClick: E(m, ["stop"])
			}, " ⋯ "), l.value ? (g(), i("div", Ea, [
				a("button", {
					type: "button",
					class: "block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover",
					onClick: o[0] ||= (e) => {
						l.value = !1, c("open", n.trip);
					}
				}, " Open trip "),
				o[2] ||= a("p", { class: "px-2 pt-1.5 pb-0.5 text-xs font-medium text-muted" }, "Move to", -1),
				(g(!0), i(e, null, v(n.statuses.filter((e) => e.id !== n.trip.status_id), (e) => (g(), i("button", {
					key: e.id,
					type: "button",
					class: "block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover",
					onClick: (t) => h(e.id)
				}, b(e.name), 9, Da))), 128)),
				n.trip.cancelled_at ? r("", !0) : (g(), i("button", {
					key: 0,
					type: "button",
					class: "mt-1 block w-full rounded px-2 py-1.5 text-start text-sm text-status-red hover:bg-hover",
					onClick: y
				}, " Cancel trip "))
			])) : r("", !0)])]),
			a("p", Oa, b(f.value), 1),
			n.trip.lorry_no ? (g(), i("p", ka, "🚛 " + b(n.trip.lorry_no), 1)) : r("", !0),
			a("div", Aa, [a("span", ja, b(n.trip.lr_count) + " LR" + b(n.trip.lr_count === 1 ? "" : "s") + " · " + b(x(I)(n.trip.revenue)), 1), a("span", { class: d(["text-sm font-semibold", p.value]) }, b(x(I)(n.trip.profit)), 3)])
		], 2));
	}
}), Na = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, Pa = {
	key: 1,
	class: "rounded-2xl border border-line-default bg-surface p-12 text-center"
}, Fa = {
	key: 2,
	class: "flex items-stretch gap-4 overflow-x-auto pb-4"
}, Ia = { class: "flex shrink-0 items-center gap-2 border-b border-line-default bg-surface-secondary/60 px-3 py-2.5" }, La = { class: "truncate text-sm font-semibold text-heading" }, Ra = { class: "ml-auto shrink-0 rounded-full bg-surface-tertiary px-2 py-0.5 text-xs font-medium text-muted" }, za = ["data-status-id"], Ba = ["data-trip-id"], Va = {
	key: 0,
	class: "rounded-lg border border-dashed border-line-default py-10 text-center text-xs text-muted"
}, Ha = /* @__PURE__ */ c({
	__name: "TripsBoardView",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {},
		filters: {},
		statuses: {},
		loading: { type: Boolean }
	},
	setup(n) {
		let o = n, c = _([]), l = _(!0), u = /* @__PURE__ */ new Map(), d = /* @__PURE__ */ new Map(), h = t(() => o.statuses.map((e) => ({
			status: e,
			cards: c.value.filter((t) => t.status_id === e.id).sort((e, t) => e.board_position - t.board_position)
		}))), y = t(() => !l.value && c.value.length === 0);
		async function x() {
			l.value = !0;
			try {
				c.value = await je(o.client, ba(o.filters));
			} catch (e) {
				o.notify("error", F(e, "Unable to load the board."));
			} finally {
				l.value = !1;
			}
		}
		m(() => {
			x();
		}), C(() => _a(o.filters), () => void x()), p(() => {
			for (let e of u.values()) e.destroy();
			u.clear(), d.clear();
		});
		function S(e) {
			return (t) => {
				if (!(t instanceof HTMLElement)) {
					u.get(e)?.destroy(), u.delete(e), d.delete(e);
					return;
				}
				d.get(e) !== t && (u.get(e)?.destroy(), d.set(e, t), u.set(e, Q.create(t, {
					group: "trips-board",
					animation: 150,
					onEnd: (e) => void T(e)
				})));
			};
		}
		function w(e) {
			let t = e.item, n = e.oldIndex ?? 0;
			t.parentNode?.removeChild(t), e.from.insertBefore(t, e.from.children[n] ?? null);
		}
		async function T(e) {
			let t = Number(e.item.dataset.tripId), n = Number(e.to.dataset.statusId);
			if (w(e), !t || Number.isNaN(n)) return;
			let r = c.value.filter((e) => e.status_id === n && e.id !== t).sort((e, t) => e.board_position - t.board_position), i = r[e.newIndex - 1], a = r[e.newIndex], s = i && a ? (i.board_position + a.board_position) / 2 : a ? a.board_position - .5 : i ? i.board_position + .5 : 1;
			try {
				let e = await Fe(o.client, t, n, s);
				c.value = c.value.map((n) => n.id === t ? e : n);
			} catch (e) {
				o.notify("error", F(e, "Unable to move the trip.")), await x();
			}
		}
		function E(e) {
			o.router?.push(A.trip(e.id));
		}
		async function D(e, t) {
			try {
				let n = await Fe(o.client, e.id, t, null);
				c.value = c.value.map((t) => t.id === e.id ? n : t);
			} catch (e) {
				o.notify("error", F(e, "Unable to move the trip."));
			}
		}
		async function O(e) {
			if (window.confirm("Cancel this trip? It stays on the board, greyed out, and can never be billed.")) try {
				await Ie(o.client, e.id), o.notify("success", "Trip cancelled."), await x();
			} catch (e) {
				o.notify("error", F(e, "Unable to cancel the trip."));
			}
		}
		return (t, o) => (g(), i("div", null, [l.value ? (g(), i("div", Na, "Loading the board…")) : y.value ? (g(), i("div", Pa, [...o[0] ||= [
			a("p", { class: "text-4xl" }, "🚛", -1),
			a("h2", { class: "mt-3 text-lg font-semibold text-heading" }, "No trips yet", -1),
			a("p", { class: "mt-1 text-sm text-muted" }, " Your board is empty. Create your first trip by mapping an existing LR Receipt, or by filling a new one. ", -1)
		]])) : (g(), i("div", Fa, [(g(!0), i(e, null, v(h.value, (t) => (g(), i("section", {
			key: t.status.id,
			class: "flex w-72 shrink-0 flex-col overflow-hidden rounded-xl border border-line-default bg-surface shadow-sm"
		}, [
			a("div", {
				class: "h-1 w-full shrink-0",
				style: f({ backgroundColor: t.status.colour ?? "#94a3b8" })
			}, null, 4),
			a("header", Ia, [
				a("span", {
					class: "inline-block h-2.5 w-2.5 shrink-0 rounded-full",
					style: f({ backgroundColor: t.status.colour ?? "#94a3b8" })
				}, null, 4),
				a("span", La, b(t.status.name), 1),
				a("span", Ra, b(t.cards.length), 1)
			]),
			a("div", {
				ref_for: !0,
				ref: S(t.status.id),
				"data-status-id": t.status.id,
				class: "min-h-44 flex-1 space-y-2 bg-surface-tertiary/40 p-2"
			}, [(g(!0), i(e, null, v(t.cards, (e) => (g(), i("div", {
				key: e.id,
				"data-trip-id": e.id
			}, [s(Ma, {
				trip: e,
				statuses: n.statuses,
				onOpen: E,
				onMove: D,
				onCancel: O
			}, null, 8, ["trip", "statuses"])], 8, Ba))), 128)), t.cards.length === 0 ? (g(), i("p", Va, " Drop trips here ")) : r("", !0)], 8, za)
		]))), 128))]))]));
	}
}), Ua = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, Wa = {
	key: 1,
	class: "rounded-2xl border border-line-default bg-surface p-12 text-center"
}, Ga = {
	key: 2,
	class: "overflow-x-auto rounded-2xl border border-line-default bg-surface"
}, Ka = { class: "w-full text-sm" }, qa = ["onClick"], Ja = { class: "p-3 font-medium text-heading" }, Ya = { class: "p-3 text-body" }, Xa = { class: "p-3 text-body" }, Za = { class: "p-3 text-body" }, Qa = { class: "p-3 text-body" }, $a = { class: "p-3 text-end text-body" }, eo = { class: "p-3 text-end text-body" }, to = /* @__PURE__ */ c({
	__name: "TripsListView",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {},
		filters: {},
		statuses: {},
		loading: { type: Boolean }
	},
	setup(n) {
		let r = n, o = _([]), s = _(!0), c = t(() => !s.value && o.value.length === 0), l = t(() => [...o.value].sort((e, t) => t.trip_no - e.trip_no)), u = (e) => r.statuses.find((t) => t.id === e)?.name ?? "—";
		async function f() {
			s.value = !0;
			try {
				o.value = await je(r.client, ba(r.filters));
			} catch (e) {
				r.notify("error", F(e, "Unable to load trips."));
			} finally {
				s.value = !1;
			}
		}
		m(() => {
			f();
		}), C(() => _a(r.filters), () => void f());
		function p(e) {
			r.router?.push(A.trip(e.id));
		}
		return (t, n) => (g(), i("div", null, [s.value ? (g(), i("div", Ua, "Loading trips…")) : c.value ? (g(), i("div", Wa, [...n[0] ||= [
			a("p", { class: "text-4xl" }, "🚛", -1),
			a("h2", { class: "mt-3 text-lg font-semibold text-heading" }, "No trips found", -1),
			a("p", { class: "mt-1 text-sm text-muted" }, "Try adjusting your filters, or create a new trip.", -1)
		]])) : (g(), i("div", Ga, [a("table", Ka, [n[1] ||= a("thead", null, [a("tr", { class: "border-b border-line-default text-start text-xs uppercase text-muted" }, [
			a("th", { class: "p-3 text-start font-medium" }, "#"),
			a("th", { class: "p-3 text-start font-medium" }, "Route"),
			a("th", { class: "p-3 text-start font-medium" }, "Customer"),
			a("th", { class: "p-3 text-start font-medium" }, "Lorry"),
			a("th", { class: "p-3 text-start font-medium" }, "Status"),
			a("th", { class: "p-3 text-end font-medium" }, "Revenue"),
			a("th", { class: "p-3 text-end font-medium" }, "Cost"),
			a("th", { class: "p-3 text-end font-medium" }, "Profit")
		])], -1), a("tbody", null, [(g(!0), i(e, null, v(l.value, (e) => (g(), i("tr", {
			key: e.id,
			class: d(["cursor-pointer border-b border-line-light hover:bg-hover", { "opacity-50": e.cancelled_at }]),
			onClick: (t) => p(e)
		}, [
			a("td", Ja, "#" + b(e.trip_no), 1),
			a("td", Ya, b(e.from_city ?? "—") + " → " + b(e.to_city ?? "—"), 1),
			a("td", Xa, b(e.customers.map((e) => e.name).filter(Boolean).join(", ") || "—"), 1),
			a("td", Za, b(e.lorry_no ?? "—"), 1),
			a("td", Qa, b(u(e.status_id)), 1),
			a("td", $a, b(x(I)(e.revenue)), 1),
			a("td", eo, b(x(I)(e.cost)), 1),
			a("td", { class: d(["p-3 text-end font-semibold", e.profit >= 0 ? "text-status-green" : "text-status-red"]) }, b(x(I)(e.profit)), 3)
		], 10, qa))), 128))])])]))]));
	}
}), no = { class: "mb-4 flex items-center justify-between gap-3" }, ro = { class: "flex items-center gap-2" }, io = { class: "text-sm font-semibold text-heading" }, ao = { class: "text-sm text-muted" }, oo = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, so = { class: "grid grid-cols-1 gap-3 md:grid-cols-7" }, co = { class: "mb-2 flex items-center justify-between" }, lo = { class: "text-xs font-medium text-muted" }, uo = {
	key: 0,
	class: "rounded-full bg-surface-tertiary px-2 py-0.5 text-xs text-muted"
}, fo = { class: "flex-1 space-y-2" }, po = ["onClick"], mo = { class: "truncate text-xs font-medium text-heading" }, ho = { class: "mt-0.5 text-xs text-muted" }, go = {
	key: 0,
	class: "mt-2 border-t border-line-light pt-1 text-xs text-muted"
}, _o = {
	key: 0,
	class: "mt-4"
}, vo = { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, yo = { class: "flex flex-wrap gap-2" }, bo = ["onClick"], xo = { class: "text-xs font-medium text-heading" }, So = { class: "text-xs text-muted" }, Co = /* @__PURE__ */ c({
	__name: "TripsWeekView",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {},
		filters: {},
		statuses: {},
		loading: { type: Boolean }
	},
	setup(n) {
		let c = n, l = _([]), u = _(!0), f = _(M(/* @__PURE__ */ new Date())), p = [
			"Sun",
			"Mon",
			"Tue",
			"Wed",
			"Thu",
			"Fri",
			"Sat"
		], h = t(() => p.map((e, t) => {
			let n = new Date(f.value);
			n.setDate(n.getDate() + t);
			let r = ee(n), i = l.value.filter((e) => e.pickup_date === r);
			return {
				name: e,
				date: n,
				dateStr: r,
				isToday: r === ee(/* @__PURE__ */ new Date()),
				trips: i,
				revenue: i.reduce((e, t) => e + t.revenue, 0)
			};
		})), S = t(() => l.value.filter((e) => !e.pickup_date)), T = t(() => {
			let e = f.value, t = new Date(e);
			t.setDate(t.getDate() + 6);
			let n = (e) => e.toLocaleDateString(void 0, {
				month: "short",
				day: "numeric"
			});
			return `${n(e)} – ${n(t)}`;
		});
		async function E() {
			u.value = !0;
			try {
				l.value = await je(c.client, ba(c.filters));
			} catch (e) {
				c.notify("error", F(e, "Unable to load trips."));
			} finally {
				u.value = !1;
			}
		}
		m(() => {
			E();
		}), C(() => _a(c.filters), () => void E());
		function D() {
			let e = new Date(f.value);
			e.setDate(e.getDate() - 7), f.value = e;
		}
		function O() {
			let e = new Date(f.value);
			e.setDate(e.getDate() + 7), f.value = e;
		}
		function k() {
			f.value = M(/* @__PURE__ */ new Date());
		}
		function j(e) {
			c.router?.push(A.trip(e.id));
		}
		function M(e) {
			let t = new Date(e);
			return t.setHours(0, 0, 0, 0), t.setDate(t.getDate() - t.getDay()), t;
		}
		function ee(e) {
			return `${e.getFullYear()}-${String(e.getMonth() + 1).padStart(2, "0")}-${String(e.getDate()).padStart(2, "0")}`;
		}
		return (t, n) => {
			let c = y("BaseButton");
			return g(), i("div", null, [a("div", no, [a("div", ro, [
				s(c, {
					size: "sm",
					variant: "white",
					onClick: D
				}, {
					default: w(() => [...n[0] ||= [o("←", -1)]]),
					_: 1
				}),
				a("span", io, b(T.value), 1),
				s(c, {
					size: "sm",
					variant: "white",
					onClick: O
				}, {
					default: w(() => [...n[1] ||= [o("→", -1)]]),
					_: 1
				}),
				s(c, {
					size: "sm",
					variant: "white",
					onClick: k
				}, {
					default: w(() => [...n[2] ||= [o("Today", -1)]]),
					_: 1
				})
			]), a("span", ao, b(l.value.length) + " trips this week", 1)]), u.value ? (g(), i("div", oo, "Loading the week…")) : (g(), i(e, { key: 1 }, [a("div", so, [(g(!0), i(e, null, v(h.value, (t) => (g(), i("div", {
				key: t.dateStr,
				class: d(["flex min-h-48 flex-col rounded-xl border bg-surface p-2", t.isToday ? "border-primary-400" : "border-line-default"])
			}, [
				a("div", co, [a("div", null, [a("p", lo, b(t.name), 1), a("p", { class: d(["text-lg font-semibold", t.isToday ? "text-primary-500" : "text-heading"]) }, b(t.date.getDate()), 3)]), t.trips.length > 0 ? (g(), i("span", uo, b(t.trips.length), 1)) : r("", !0)]),
				a("div", fo, [(g(!0), i(e, null, v(t.trips, (e) => (g(), i("div", {
					key: e.id,
					class: "cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover",
					onClick: (t) => j(e)
				}, [a("p", mo, "#" + b(e.trip_no) + " · " + b(e.from_city ?? "—") + " → " + b(e.to_city ?? "—"), 1), a("p", ho, b(e.lorry_no ?? "No lorry") + " · " + b(x(I)(e.revenue)), 1)], 8, po))), 128))]),
				t.trips.length > 0 ? (g(), i("p", go, b(x(I)(t.revenue)), 1)) : r("", !0)
			], 2))), 128))]), S.value.length > 0 ? (g(), i("div", _o, [a("p", vo, " Unscheduled (" + b(S.value.length) + ") ", 1), a("div", yo, [(g(!0), i(e, null, v(S.value, (e) => (g(), i("div", {
				key: e.id,
				class: "cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover",
				onClick: (t) => j(e)
			}, [a("p", xo, "#" + b(e.trip_no), 1), a("p", So, b(e.from_city ?? "—") + " → " + b(e.to_city ?? "—"), 1)], 8, bo))), 128))])])) : r("", !0)], 64))]);
		};
	}
}), wo = { class: "mt-4 flex flex-wrap items-end gap-3" }, To = { class: "min-w-44 flex-1" }, Eo = { class: "min-w-44 flex-1" }, Do = { class: "min-w-44 flex-1" }, Oo = { class: "flex items-center gap-1.5 pb-2.5 text-sm text-body" }, ko = ["checked"], Ao = 350, jo = /* @__PURE__ */ c({
	__name: "TripFilters",
	props: {
		modelValue: {},
		statuses: {}
	},
	emits: ["update:modelValue"],
	setup(e, { emit: n }) {
		let c = e, l = n, u = _(c.modelValue.search), d, f = t(() => [{
			id: "",
			label: "All statuses"
		}, ...c.statuses.map((e) => ({
			id: String(e.id),
			label: e.name
		}))]), m = t({
			get: () => v(f.value, c.modelValue.status),
			set: (e) => b({ status: e?.id ?? "" })
		}), h = t(() => ga(c.modelValue));
		C(() => c.modelValue.search, (e) => {
			e !== u.value && (u.value = e);
		}), C(u, (e) => {
			clearTimeout(d), d = setTimeout(() => b({ search: e.trim() }), Ao);
		}), p(() => clearTimeout(d));
		function v(e, t) {
			return e.find((e) => e.id === t) ?? e[0];
		}
		function b(e) {
			l("update:modelValue", {
				...c.modelValue,
				...e
			});
		}
		function x() {
			b({ unbilled: c.modelValue.unbilled === "1" ? "" : "1" });
		}
		function S() {
			u.value = "", l("update:modelValue", { ...pa });
		}
		return (t, n) => {
			let c = y("BaseSelectInput"), l = y("BaseCustomerSelectInput"), d = y("BaseInput");
			return g(), i("div", wo, [
				a("label", To, [n[3] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Status", -1), s(c, {
					modelValue: m.value,
					"onUpdate:modelValue": n[0] ||= (e) => m.value = e,
					options: f.value,
					"label-key": "label"
				}, null, 8, ["modelValue", "options"])]),
				a("label", Eo, [n[4] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Customer", -1), s(l, {
					"model-value": e.modelValue.customer ? Number(e.modelValue.customer) : null,
					"can-deselect": "",
					placeholder: "All customers",
					"onUpdate:modelValue": n[1] ||= (e) => b({ customer: e ? String(e) : "" })
				}, null, 8, ["model-value"])]),
				a("label", Do, [n[5] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Search", -1), s(d, {
					modelValue: u.value,
					"onUpdate:modelValue": n[2] ||= (e) => u.value = e,
					type: "text",
					name: "search",
					autocomplete: "off",
					placeholder: "Search route, lorry, goods..."
				}, null, 8, ["modelValue"])]),
				a("label", Oo, [a("input", {
					type: "checkbox",
					class: "accent-primary-600",
					checked: e.modelValue.unbilled === "1",
					onChange: x
				}, null, 40, ko), n[6] ||= o(" Unbilled only ", -1)]),
				h.value ? (g(), i("button", {
					key: 0,
					type: "button",
					class: "pb-2.5 text-sm font-medium text-primary-500 hover:underline",
					onClick: S
				}, " Clear ")) : r("", !0)
			]);
		};
	}
}), Mo = {
	class: "inline-flex overflow-hidden rounded-lg border border-line-default",
	"aria-label": "Trip views"
}, No = ["aria-current", "onClick"], Po = { class: "max-sm:sr-only" }, Fo = /* @__PURE__ */ c({
	__name: "TripViewSwitcher",
	props: {
		active: {},
		query: {}
	},
	emits: ["select"],
	setup(t, { emit: n }) {
		let r = t, o = n, c = [
			{
				id: "board",
				name: j.board,
				label: "Board",
				icon: "ViewColumnsIcon"
			},
			{
				id: "list",
				name: j.list,
				label: "List",
				icon: "ListBulletIcon"
			},
			{
				id: "week",
				name: j.week,
				label: "Week",
				icon: "CalendarDaysIcon"
			}
		];
		function l(e) {
			return r.active === e.name || e.id === "board" && r.active === j.trips;
		}
		function u(e) {
			l(e) || o("select", {
				name: e.name,
				query: r.query
			});
		}
		return (t, n) => {
			let r = y("BaseIcon");
			return g(), i("nav", Mo, [(g(), i(e, null, v(c, (e) => a("button", {
				key: e.id,
				type: "button",
				class: d(["flex items-center gap-1.5 border-e border-line-default px-3 py-1.5 text-sm font-medium last:border-e-0", l(e) ? "bg-primary-50 text-primary-500" : "bg-surface text-muted hover:bg-hover hover:text-heading"]),
				"aria-current": l(e) ? "page" : void 0,
				onClick: (t) => u(e)
			}, [s(r, {
				name: e.icon,
				class: "h-4 w-4"
			}, null, 8, ["name"]), a("span", Po, b(e.label), 1)], 10, No)), 64))]);
		};
	}
}), Io = { class: "flex flex-wrap items-center justify-end gap-3" }, Lo = /* @__PURE__ */ c({
	__name: "TripsPage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(e) {
		let r = e, i = r.router, c = _([]), l = _(!0), u = t(() => r.router.currentRoute.value), f = t(() => ma(u.value.query)), p = t(() => ha(f.value)), h = t(() => String(u.value.name ?? ""));
		C(h, (e) => v(e)), m(() => {
			v(h.value), b();
		});
		function v(e) {
			e === j.trips && i.replace({
				name: j.board,
				query: p.value
			});
		}
		async function b() {
			l.value = !0;
			try {
				c.value = await Me(r.client);
			} catch (e) {
				r.notify("error", F(e, "Unable to load statuses."));
			} finally {
				l.value = !1;
			}
		}
		function S(e) {
			va(e, f.value) || i.replace({
				name: h.value === j.trips ? j.board : h.value,
				query: ha(e)
			});
		}
		function T(e) {
			i.push(e);
		}
		function E() {
			i.push(A.new);
		}
		return (e, t) => {
			let r = y("BaseBreadcrumbItem"), i = y("BaseBreadcrumb"), u = y("BaseIcon"), m = y("BaseButton"), _ = y("router-link"), v = y("BasePageHeader"), b = y("router-view"), C = y("BasePage");
			return g(), n(C, null, {
				default: w(() => [
					s(v, { title: "Trips" }, {
						actions: w(() => [a("div", Io, [
							s(Fo, {
								active: h.value,
								query: p.value,
								onSelect: T
							}, null, 8, ["active", "query"]),
							s(_, { to: x(A).reports }, {
								default: w(() => [s(m, { variant: "white" }, {
									left: w((e) => [s(u, {
										name: "ChartBarIcon",
										class: d(e.class)
									}, null, 8, ["class"])]),
									default: w(() => [t[0] ||= o(" Reports ", -1)]),
									_: 1
								})]),
								_: 1
							}, 8, ["to"]),
							s(m, {
								variant: "primary",
								onClick: E
							}, {
								left: w((e) => [s(u, {
									name: "PlusIcon",
									class: d(e.class)
								}, null, 8, ["class"])]),
								default: w(() => [t[1] ||= o(" New Trip ", -1)]),
								_: 1
							})
						])]),
						default: w(() => [s(i, null, {
							default: w(() => [s(r, {
								title: "Dashboard",
								to: "/admin/dashboard"
							}), s(r, {
								title: "Trips",
								to: "#",
								active: ""
							})]),
							_: 1
						})]),
						_: 1
					}),
					s(jo, {
						"model-value": f.value,
						statuses: c.value,
						"onUpdate:modelValue": S
					}, null, 8, ["model-value", "statuses"]),
					s(b, {
						filters: f.value,
						statuses: c.value,
						loading: l.value
					}, null, 8, [
						"filters",
						"statuses",
						"loading"
					])
				]),
				_: 1
			});
		};
	}
}), Ro = `${O}:view-trip`;
function zo(e) {
	e.registerPage({
		id: "trips",
		module: O,
		path: "",
		component: M(e, Lo),
		meta: {
			ability: Ro,
			title: "trips.title"
		},
		children: [
			{
				id: "board",
				module: O,
				path: "",
				component: M(e, Ha),
				meta: {
					ability: Ro,
					title: "trips.title"
				}
			},
			{
				id: "list",
				module: O,
				path: "list",
				component: M(e, to),
				meta: {
					ability: Ro,
					title: "trips.title"
				}
			},
			{
				id: "week",
				module: O,
				path: "week",
				component: M(e, Co),
				meta: {
					ability: Ro,
					title: "trips.title"
				}
			}
		]
	}), e.registerPage({
		id: "new",
		module: O,
		path: "new",
		component: M(e, Dt),
		meta: {
			ability: Ro,
			title: "trips.title"
		}
	}), e.registerPage({
		id: "trip",
		module: O,
		path: ":id(\\d+)",
		component: M(e, ur),
		meta: {
			ability: Ro,
			title: "trips.title"
		}
	});
}
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	n.addMessages(D), zo(n), Ae(n);
});
//#endregion
