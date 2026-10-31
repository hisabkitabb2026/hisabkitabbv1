const { Fragment: e, computed: t, createBlock: n, createCommentVNode: r, createElementBlock: i, createElementVNode: a, createStaticVNode: o, createTextVNode: s, createVNode: c, defineComponent: l, getCurrentInstance: u, h: d, normalizeClass: f, normalizeStyle: p, onBeforeUnmount: m, onMounted: h, onUnmounted: g, openBlock: _, ref: v, renderList: y, resolveComponent: b, toDisplayString: x, unref: S, vModelSelect: C, watch: w, withCtx: T, withDirectives: E, withModifiers: D } = window.__invoiceshelf_vue;
//#region resources/js/messages.ts
var O = { en: { trips: {
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
} } }, k = "trips", A = `/admin/modules/${k}`, j = {
	board: A,
	list: `${A}/list`,
	week: `${A}/week`,
	new: `${A}/new`,
	trip: (e) => `${A}/${e}`,
	reports: `${A}/reports`,
	invoiceView: (e) => `/admin/invoices/${e}/view`,
	invoiceCreate: (e) => `/admin/invoices/create?template=${e}`
}, M = {
	trips: `extension.page.${k}.trips`,
	board: `extension.page.${k}.trips.board`,
	list: `extension.page.${k}.trips.list`,
	week: `extension.page.${k}.trips.week`,
	trip: `extension.page.${k}.trip`,
	reports: `extension.page.${k}.reports`
};
function N(e, t) {
	return l({ setup: (n, { attrs: r }) => () => d(t, {
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
	return u()?.appContext.config.globalProperties.$t ?? ((e) => e);
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
var ne = (e) => e.getFullYear() + "-" + String(e.getMonth() + 1).padStart(2, "0") + "-" + String(e.getDate()).padStart(2, "0");
function P(e) {
	let t = new Date(e);
	return t.setHours(0, 0, 0, 0), t;
}
function re(e) {
	let t = new Date(e);
	return t.setHours(23, 59, 59, 999), t;
}
function ie(e) {
	let t = P(e), n = (t.getDay() + 6) % 7;
	return t.setDate(t.getDate() - n), t;
}
function ae(e) {
	let t = ie(e), n = new Date(t);
	return n.setDate(t.getDate() + 6), re(n);
}
function oe(e) {
	return P(new Date(e.getFullYear(), e.getMonth(), 1));
}
function se(e) {
	return re(new Date(e.getFullYear(), e.getMonth() + 1, 0));
}
function ce(e) {
	let t = Math.floor(e.getMonth() / 3) * 3;
	return P(new Date(e.getFullYear(), t, 1));
}
function le(e) {
	let t = Math.floor(e.getMonth() / 3) * 3;
	return re(new Date(e.getFullYear(), t + 3, 0));
}
function ue(e) {
	return P(new Date(e.getFullYear(), 0, 1));
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
		from: ne(fe[e](t)),
		to: ne(pe[e](t))
	};
}
function he(e, t) {
	let n = new Date(t);
	return e === "isoWeek" ? n.setDate(n.getDate() - 7) : e === "month" ? n.setMonth(n.getMonth() - 1) : e === "quarter" ? n.setMonth(n.getMonth() - 3) : n.setFullYear(n.getFullYear() - 1), {
		from: ne(fe[e](n)),
		to: ne(pe[e](n))
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
			from: ne(t),
			to: ne(t)
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
}, Se = ["src"], Ce = /* @__PURE__ */ l({
	__name: "TripReportPdfPane",
	props: {
		client: { type: [Function, Object] },
		path: {}
	},
	setup(e, { expose: t }) {
		let n = e, r = v("idle"), o = v(null), l = null, u = null, d = 0;
		function f() {
			o.value && URL.revokeObjectURL(o.value), o.value = null, l = null, u = null;
		}
		async function p(e) {
			let t = ++d;
			f(), r.value = "loading";
			try {
				let i = await n.client.get(e, { responseType: "blob" });
				if (t !== d) return;
				let a = i.data;
				if (a.type.startsWith("text/html") || a.type.startsWith("application/json")) throw Error("Server returned a message instead of a PDF");
				l = a, u = e, o.value = URL.createObjectURL(a), r.value = "ready";
			} catch {
				t === d && (r.value = "error");
			}
		}
		function h() {
			n.path && p(n.path);
		}
		async function g(e) {
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
		return w(() => n.path, (e) => {
			if (e) {
				p(e);
				return;
			}
			d++, f(), r.value = "idle";
		}, { immediate: !0 }), m(f), t({ view: g }), (e, t) => {
			let n = b("BaseSpinner"), l = b("BaseIcon"), u = b("BaseButton");
			return _(), i("div", null, [r.value === "loading" || r.value === "idle" ? (_(), i("div", be, [c(n, { class: "w-8 h-8 text-primary-400" })])) : r.value === "error" ? (_(), i("div", xe, [
				c(l, {
					name: "ExclamationCircleIcon",
					class: "w-12 h-12 text-muted"
				}),
				t[1] ||= a("p", { class: "text-sm text-muted" }, "Unable to load the report.", -1),
				c(u, {
					variant: "primary-outline",
					size: "sm",
					onClick: h
				}, {
					default: T(() => [...t[0] ||= [s("Retry", -1)]]),
					_: 1
				})
			])) : (_(), i("iframe", {
				key: 2,
				src: o.value ?? void 0,
				title: "Trip Report PDF",
				class: "w-full h-screen border border-line-default border-solid rounded bg-surface"
			}, null, 8, Se))]);
		};
	}
}), we = { class: "grid gap-8 md:grid-cols-12 pt-6" }, Te = { class: "col-span-12 md:col-span-4 space-y-4" }, Ee = { class: "hidden pt-2 md:block" }, De = { class: "col-span-12 md:col-span-8" }, Oe = /* @__PURE__ */ l({
	__name: "TripReportsPage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(r) {
		let o = r, l = ve(ee()), u = v(ye(l[2])), d = ["customer", "lr"], p = {
			customer: "By Customer",
			lr: "By LR"
		}, m = v("customer"), g = t(() => Math.max(0, d.indexOf(m.value)));
		function x(e) {
			let t = Object.keys(p).find((t) => p[t] === e.title);
			t && t !== m.value && (m.value = t);
		}
		let C = v(null), E = v(null), O = ge(), k = v(O.from), A = v(O.to), M = v(""), N = v(""), ne = t(() => C.value), P = t(() => {
			let e = "/api/v1/trips/reports/pdf?from_date=" + k.value + "&to_date=" + A.value + "&type=" + m.value;
			return M.value.trim() && (e += "&customer=" + encodeURIComponent(M.value.trim())), N.value.trim() && (e += "&lr_no=" + encodeURIComponent(N.value.trim())), e;
		}), re = te(o.client, () => (C.value = P.value, C.value));
		h(() => {
			C.value = P.value;
		}), w(u, (e) => {
			e.from && e.to && (k.value = e.from, A.value = e.to);
		}), w([
			k,
			A,
			m,
			M,
			N
		], () => {
			C.value = P.value;
		});
		function ie() {
			return C.value = P.value, !0;
		}
		function ae() {
			ie(), E.value?.view(C.value);
		}
		function oe() {
			o.router.push(j.board);
		}
		return (t, o) => {
			let h = b("BaseBreadcrumbItem"), v = b("BaseBreadcrumb"), C = b("BaseIcon"), w = b("BaseButton"), O = b("BasePageHeader"), k = b("BaseTab"), A = b("BaseTabGroup"), ee = b("BasePeriodPicker"), te = b("BaseInputGroup"), P = b("BaseInput"), se = b("BasePage");
			return _(), n(se, null, {
				default: T(() => [
					c(O, { title: "Trip Reports" }, {
						actions: T(() => [c(w, {
							variant: "white",
							onClick: oe
						}, {
							left: T((e) => [c(C, {
								name: "ArrowLeftIcon",
								class: f(e.class)
							}, null, 8, ["class"])]),
							default: T(() => [o[3] ||= s(" Back to Trips ", -1)]),
							_: 1
						}), c(w, {
							variant: "primary",
							class: "ms-4",
							onClick: S(re)
						}, {
							left: T((e) => [c(C, {
								name: "ArrowDownTrayIcon",
								class: f(e.class)
							}, null, 8, ["class"])]),
							default: T(() => [o[4] ||= s(" Download PDF ", -1)]),
							_: 1
						}, 8, ["onClick"])]),
						default: T(() => [c(v, null, {
							default: T(() => [
								c(h, {
									title: "Dashboard",
									to: "/admin/dashboard"
								}),
								c(h, {
									title: "Trips",
									to: S(j).board
								}, null, 8, ["to"]),
								c(h, {
									title: "Reports",
									to: "#",
									active: ""
								})
							]),
							_: 1
						})]),
						_: 1
					}),
					(_(), n(A, {
						key: m.value,
						"default-index": g.value,
						class: "p-2",
						onChange: x
					}, {
						default: T(() => [(_(), i(e, null, y(d, (e) => c(k, {
							key: e,
							title: p[e],
							"tab-panel-container": "px-0 py-0"
						}, null, 8, ["title"])), 64))]),
						_: 1
					}, 8, ["default-index"])),
					a("div", we, [a("div", Te, [
						c(te, { label: "Date Range" }, {
							default: T(() => [c(ee, {
								modelValue: u.value,
								"onUpdate:modelValue": o[0] ||= (e) => u.value = e,
								presets: S(l),
								block: "",
								position: "bottom-start"
							}, null, 8, ["modelValue", "presets"])]),
							_: 1
						}),
						c(te, { label: "Filter By Customer" }, {
							default: T(() => [c(P, {
								modelValue: M.value,
								"onUpdate:modelValue": [o[1] ||= (e) => M.value = e, ie],
								placeholder: "Type customer name..."
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						c(te, { label: "Filter By LR No" }, {
							default: T(() => [c(P, {
								modelValue: N.value,
								"onUpdate:modelValue": [o[2] ||= (e) => N.value = e, ie],
								placeholder: "Type LR number..."
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a("div", Ee, [c(w, {
							variant: "primary",
							class: "justify-center w-full",
							type: "submit",
							onClick: D(ie, ["prevent"])
						}, {
							left: T((e) => [c(C, {
								name: "ArrowPathIcon",
								class: f(e.class)
							}, null, 8, ["class"])]),
							default: T(() => [o[5] ||= s(" Update Report ", -1)]),
							_: 1
						})])
					]), a("div", De, [c(Ce, {
						ref_key: "pdfPane",
						ref: E,
						client: r.client,
						path: ne.value,
						class: "hidden md:block"
					}, null, 8, ["client", "path"]), a("button", {
						type: "button",
						class: "flex items-center justify-center w-full gap-2 px-5 text-sm font-medium transition-colors rounded-lg h-11 md:hidden bg-btn-primary text-on-primary hover:bg-btn-primary-hover",
						onClick: ae
					}, [c(C, {
						name: "DocumentTextIcon",
						class: "w-5 h-5"
					}), o[6] ||= s(" View PDF ", -1)])])])
				]),
				_: 1
			});
		};
	}
}), ke = `${k}:view-trip`;
function Ae(e) {
	e.registerPage({
		id: "reports",
		module: k,
		path: "reports",
		component: N(e, Oe),
		meta: {
			ability: ke,
			title: "Trip Reports"
		}
	});
}
//#endregion
//#region resources/js/api/trips.ts
var F = "/api/v1/trips";
async function je(e, t = {}) {
	let { data: n } = await e.get(F, { params: t });
	return n.data;
}
async function Me(e) {
	let { data: t } = await e.get(`${F}/statuses`);
	return t.data;
}
async function Ne(e, t) {
	let { data: n } = await e.get(`${F}/${t}`);
	return n.data;
}
async function Pe(e, t, n) {
	let { data: r } = await e.post(`${F}/from-lrs`, {
		invoice_ids: t,
		...n
	});
	return r.data;
}
async function Fe(e, t, n, r) {
	let { data: i } = await e.post(`${F}/${t}/move`, {
		status_id: n,
		position: r
	});
	return i.data;
}
async function Ie(e, t) {
	let { data: n } = await e.post(`${F}/${t}/cancel`);
	return n.data;
}
async function Le(e, t, n) {
	let { data: r } = await e.patch(`${F}/${t}/status`, { status_id: n });
	return r.data;
}
async function Re(e, t = "lr", n = null, r = null, i = !1) {
	let { data: a } = await e.get(`${F}/unlinked-lrs`, { params: {
		type: t,
		customer_id: n,
		trip_id: r,
		all: i ? 1 : void 0
	} });
	return a.data;
}
async function ze(e, t, n, r) {
	let { data: i } = await e.post(`${F}/${t}/receipts`, {
		invoice_id: n,
		type: r
	});
	return i.data;
}
async function Be(e, t) {
	let { data: n } = await e.post(`${F}/${t}/fetch-lorry-receipt`);
	return n.data;
}
async function Ve(e, t) {
	let { data: n } = await e.post(`${F}/${t}/fetch-invoice-receipt`);
	return n.data;
}
async function He(e, t, n, r) {
	let i = new FormData();
	i.append("file", n), i.append("category", r);
	let { data: a } = await e.post(`${F}/${t}/pod`, i, { headers: { "Content-Type": "multipart/form-data" } });
	return a.data;
}
async function Ue(e, t, n) {
	let { data: r } = await e.delete(`${F}/${t}/pod/${n}`);
	return r.data;
}
async function We(e, t, n) {
	let { data: r } = await e.get(`${F}/${t}/pod/${n}`, { responseType: "blob" });
	return r;
}
async function Ge(e, t, n) {
	let { data: r } = await e.delete(`${F}/${t}/receipts/${n}`);
	return r.data;
}
//#endregion
//#region resources/js/support/http.ts
function I(e, t) {
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
function L(e) {
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
var qe = { class: "pt-5 mt-5 border-t border-line-light" }, Je = { class: "flex flex-wrap items-center justify-between gap-3 mb-4" }, Ye = { class: "flex gap-2" }, Xe = {
	key: 0,
	class: "p-4 text-sm rounded-lg bg-surface-secondary text-muted"
}, Ze = {
	key: 1,
	class: "p-4 text-sm rounded-lg bg-surface-secondary text-muted",
	role: "status"
}, Qe = {
	key: 2,
	class: "space-y-3"
}, $e = { class: "flex h-[38px] items-center rounded-lg bg-surface-tertiary px-3 text-sm font-medium text-heading" }, et = { class: "justify-self-end" }, tt = { class: "flex flex-wrap justify-end gap-x-8 gap-y-2 pt-4 mt-4 text-sm border-t border-line-light" }, nt = { class: "text-muted" }, rt = { class: "font-semibold text-heading" }, it = { class: "text-heading font-semibold" }, at = /* @__PURE__ */ l({
	__name: "TripCreatePage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(o) {
		let l = o, u = v(Ke()), d = v(""), p = v(null), m = v(""), h = v([]), g = v([]), C = v(!1), E = v(!1), O = t(() => h.value);
		async function k(e) {
			if (g.value = [], !e) {
				h.value = [];
				return;
			}
			C.value = !0;
			try {
				h.value = await Re(l.client, "lr", e);
			} catch (e) {
				l.notify("error", I(e, "Unable to load LR receipts.")), h.value = [];
			} finally {
				C.value = !1;
			}
		}
		w(p, (e, t) => {
			t !== void 0 && e !== t && k(e);
		});
		function A(e) {
			if (!e) return;
			let t = typeof e == "object" && e ? e.id : e, n = Number(t);
			if (!(!n || isNaN(n))) return h.value.find((e) => e.id === n);
		}
		function M(e) {
			return A(e)?.amount ?? 0;
		}
		function N(e) {
			let t = e ? Number(typeof e == "object" ? e.id : e) : null, n = g.value.map((e) => {
				let t = typeof e.receipt_id == "object" && e.receipt_id !== null ? e.receipt_id.id : e.receipt_id;
				return t ? Number(t) : null;
			}).filter((e) => e !== null && e !== t);
			return h.value.filter((e) => !n.includes(e.id)).map((e) => ({
				id: e.id,
				label: `${e.invoice_number} · ${e.from_city ?? "—"} → ${e.to_city ?? "—"} (${L(e.amount)})`
			}));
		}
		let ee = t(() => g.value.map((e) => {
			let t = typeof e.receipt_id == "object" && e.receipt_id !== null ? e.receipt_id.id : e.receipt_id;
			return t ? Number(t) : null;
		}).filter((e) => e !== null)), te = t(() => g.value.map((e) => A(e.receipt_id)).filter((e) => e !== void 0)), ne = t(() => te.value.reduce((e, t) => e + t.amount, 0));
		function P() {
			g.value.push({ receipt_id: null });
		}
		function re(e) {
			g.value.splice(e, 1);
		}
		function ie() {
			g.value = [...h.value].sort((e, t) => (e.invoice_date ?? "9999-12-31").localeCompare(t.invoice_date ?? "9999-12-31") || e.id - t.id).map((e) => ({ receipt_id: e.id }));
		}
		async function ae() {
			if (!p.value) {
				l.notify("warning", "Please select a customer first.");
				return;
			}
			if (ee.value.length === 0) {
				l.notify("warning", "Please allocate at least one LR receipt to this trip.");
				return;
			}
			E.value = !0;
			try {
				let e = await Pe(l.client, ee.value, {
					pickup_date: u.value,
					notes: m.value
				});
				l.notify("success", `Trip #${e.trip_no} created successfully.`), l.router?.push(j.trip(e.id));
			} catch (e) {
				l.notify("error", I(e, "Unable to create the trip."));
			} finally {
				E.value = !1;
			}
		}
		return (t, o) => {
			let l = b("BaseBreadcrumbItem"), h = b("BaseBreadcrumb"), v = b("BaseIcon"), w = b("BaseButton"), A = b("BasePageHeader"), ee = b("BaseDatePicker"), oe = b("BaseInputGroup"), se = b("BaseInput"), ce = b("BaseCustomerSelectInput"), le = b("BaseInputGrid"), ue = b("BaseMultiselect"), de = b("BaseTextarea"), fe = b("BaseCard"), pe = b("BasePage");
			return _(), n(pe, { class: "relative trip-create" }, {
				default: T(() => [a("form", {
					class: "flex flex-col gap-4 md:gap-5",
					onSubmit: D(ae, ["prevent"])
				}, [c(A, {
					title: "New Trip",
					"phone-actions": "bar"
				}, {
					actions: T(() => [c(w, {
						loading: E.value,
						disabled: E.value,
						variant: "primary",
						type: "submit"
					}, {
						left: T((e) => [E.value ? r("", !0) : (_(), n(v, {
							key: 0,
							name: "ArrowDownOnSquareIcon",
							class: f(e.class)
						}, null, 8, ["class"]))]),
						default: T(() => [o[4] ||= s(" Save Trip ", -1)]),
						_: 1
					}, 8, ["loading", "disabled"])]),
					default: T(() => [c(h, null, {
						default: T(() => [
							c(l, {
								title: "Home",
								to: "/admin/dashboard"
							}),
							c(l, {
								title: "Trips",
								to: S(j).board
							}, null, 8, ["to"]),
							c(l, {
								title: "New Trip",
								to: "#",
								active: ""
							})
						]),
						_: 1
					})]),
					_: 1
				}), c(fe, { "container-class": "p-4 md:p-5" }, {
					default: T(() => [
						c(le, null, {
							default: T(() => [
								c(oe, {
									label: "Date",
									required: ""
								}, {
									default: T(() => [c(ee, {
										modelValue: u.value,
										"onUpdate:modelValue": o[0] ||= (e) => u.value = e,
										"calendar-button": !0,
										"calendar-button-icon": "calendar"
									}, null, 8, ["modelValue"])]),
									_: 1
								}),
								c(oe, {
									label: "Trip Number",
									required: ""
								}, {
									default: T(() => [c(se, {
										modelValue: d.value,
										"onUpdate:modelValue": o[1] ||= (e) => d.value = e,
										placeholder: "Auto-generated"
									}, null, 8, ["modelValue"])]),
									_: 1
								}),
								c(oe, {
									label: "Customer",
									required: ""
								}, {
									default: T(() => [c(ce, {
										modelValue: p.value,
										"onUpdate:modelValue": [o[2] ||= (e) => p.value = e, k],
										placeholder: "Type or click to select",
										"show-action": ""
									}, null, 8, ["modelValue"])]),
									_: 1
								})
							]),
							_: 1
						}),
						a("section", qe, [
							a("div", Je, [o[7] ||= a("div", null, [a("h2", { class: "font-semibold text-section text-heading" }, "LR Allocations"), a("p", { class: "mt-1 text-sm text-muted" }, "Allocate one or more open LR receipts to this trip.")], -1), a("div", Ye, [c(w, {
								type: "button",
								size: "sm",
								variant: "primary-outline",
								disabled: !O.value.length,
								onClick: ie
							}, {
								default: T(() => [...o[5] ||= [s(" Allocate Oldest First ", -1)]]),
								_: 1
							}, 8, ["disabled"]), c(w, {
								type: "button",
								size: "sm",
								variant: "primary-outline",
								disabled: !p.value || !N(null).length,
								onClick: P
							}, {
								left: T((e) => [c(v, {
									name: "PlusIcon",
									class: f(e.class)
								}, null, 8, ["class"])]),
								default: T(() => [o[6] ||= s(" Add Allocation ", -1)]),
								_: 1
							}, 8, ["disabled"])])]),
							p.value ? g.value.length ? (_(), i("div", Qe, [(_(!0), i(e, null, y(g.value, (e, t) => (_(), i("div", {
								key: `${e.receipt_id}-${t}`,
								class: "grid items-end gap-3 p-3 rounded-lg bg-surface-secondary md:grid-cols-[minmax(0,1fr)_12rem_auto]"
							}, [
								c(oe, { label: "LR Receipt" }, {
									default: T(() => [c(ue, {
										modelValue: e.receipt_id,
										"onUpdate:modelValue": (t) => e.receipt_id = t,
										"value-prop": "id",
										"track-by": "id",
										label: "label",
										options: N(e.receipt_id),
										loading: C.value,
										placeholder: "Select an LR",
										searchable: ""
									}, null, 8, [
										"modelValue",
										"onUpdate:modelValue",
										"options",
										"loading"
									])]),
									_: 2
								}, 1024),
								c(oe, { label: "Amount" }, {
									default: T(() => [a("div", $e, x(e.receipt_id ? S(L)(M(e.receipt_id)) : "—"), 1)]),
									_: 2
								}, 1024),
								a("div", et, [c(w, {
									type: "button",
									size: "sm",
									variant: "gray",
									"aria-label": "Remove allocation",
									onClick: (e) => re(t)
								}, {
									default: T(() => [c(v, { name: "TrashIcon" })]),
									_: 1
								}, 8, ["onClick"])])
							]))), 128))])) : (_(), i("div", Ze, x(!C.value && !O.value.length ? "No open LR receipts for this customer." : "No allocations added yet. Click \"+ Add Allocation\" or \"Allocate Oldest First\"."), 1)) : (_(), i("div", Xe, " Select a customer before allocating this trip. ")),
							a("div", tt, [a("span", nt, [o[8] ||= s("Total LRs: ", -1), a("span", rt, x(te.value.length), 1)]), a("span", it, "Allocated: " + x(S(L)(ne.value)), 1)])
						]),
						c(oe, {
							label: "Notes",
							class: "mt-5"
						}, {
							default: T(() => [c(de, {
								modelValue: m.value,
								"onUpdate:modelValue": o[3] ||= (e) => m.value = e,
								rows: 4,
								placeholder: "Add any notes about this trip..."
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					]),
					_: 1
				})], 32)]),
				_: 1
			});
		};
	}
}), ot = { class: "space-y-3" }, st = {
	key: 1,
	class: "flex items-center justify-between text-xs text-muted"
}, ct = {
	key: 2,
	class: "flex items-center justify-between text-xs text-muted"
}, lt = {
	key: 3,
	class: "py-8 text-center text-sm text-muted"
}, ut = {
	key: 4,
	class: "rounded-lg bg-surface-secondary p-4 text-sm text-muted"
}, dt = { key: 0 }, ft = { key: 1 }, pt = { key: 2 }, mt = {
	key: 5,
	class: "max-h-72 space-y-1 overflow-y-auto"
}, ht = ["onClick"], gt = ["checked", "onChange"], _t = { class: "min-w-0 flex-1" }, vt = { class: "block font-medium text-heading" }, yt = { class: "block truncate text-xs text-muted" }, bt = { class: "text-sm font-semibold text-heading" }, xt = {
	key: 6,
	class: "text-sm text-muted"
}, St = {
	key: 7,
	class: "text-sm text-alert-warning-text"
}, Ct = { class: "flex justify-end gap-2" }, wt = /* @__PURE__ */ l({
	__name: "LrPickerModal",
	props: {
		show: { type: Boolean },
		client: { type: [Function, Object] },
		notify: { type: Function },
		type: {},
		multiple: { type: Boolean },
		customers: {},
		tripId: {}
	},
	emits: ["close", "select"],
	setup(o, { emit: l }) {
		let u = o, d = l, p = v([]), m = v([]), g = v(null), C = v(!1), E = v(!1), O = t(() => u.multiple ?? !0);
		async function k() {
			E.value = !0;
			try {
				p.value = await Re(u.client, u.type ?? "lr", g.value, u.tripId ?? null, C.value), m.value = [];
			} catch (e) {
				u.notify("error", I(e, "Unable to load the receipts."));
			} finally {
				E.value = !1;
			}
		}
		h(() => {
			u.show && k();
		}), w(() => u.show, (e) => {
			e && (C.value = !1, k());
		}), w([g, C], () => {
			k();
		});
		function A(e) {
			if (!O.value) {
				m.value = [e];
				return;
			}
			m.value.includes(e) ? m.value = m.value.filter((t) => t !== e) : m.value = [...m.value, e];
		}
		let j = t(() => p.value.filter((e) => m.value.includes(e.id)).reduce((e, t) => e + t.amount, 0)), M = t(() => {
			let e = p.value.filter((e) => m.value.includes(e.id));
			return new Set(e.map((e) => `${e.from_city}→${e.to_city}`)).size > 1;
		});
		function N() {
			m.value.length !== 0 && d("select", [...m.value]);
		}
		return (t, l) => {
			let u = b("BaseSelectInput"), h = b("BaseInputGroup"), v = b("BaseButton"), w = b("BaseModal");
			return _(), n(w, {
				show: o.show,
				onClose: l[6] ||= (e) => d("close")
			}, {
				header: T(() => [a("span", null, x(o.type === "lorry" ? "Map Lorry Receipt" : "Map LR Receipts"), 1)]),
				default: T(() => [a("div", ot, [
					o.customers && o.customers.length > 0 ? (_(), n(h, {
						key: 0,
						label: "Customer"
					}, {
						default: T(() => [c(u, {
							modelValue: g.value,
							"onUpdate:modelValue": l[0] ||= (e) => g.value = e,
							options: [{
								id: null,
								label: "All customers"
							}, ...o.customers.map((e) => ({
								id: e.id,
								label: e.name
							}))],
							"value-prop": "id",
							"label-key": "label"
						}, null, 8, ["modelValue", "options"])]),
						_: 1
					})) : r("", !0),
					o.type === "lorry" && o.tripId ? (_(), i("div", st, [a("span", null, x(C.value ? "Showing all unlinked lorry receipts" : "Showing lorry receipts matching this trip’s LR numbers"), 1), a("button", {
						type: "button",
						class: "font-medium text-primary-600 hover:underline",
						onClick: l[1] ||= (e) => C.value = !C.value
					}, x(C.value ? "Show matching only" : "Show all unlinked"), 1)])) : o.type === "lr" && o.tripId ? (_(), i("div", ct, [a("span", null, x(C.value ? "Showing all unlinked LR receipts" : "Showing unlinked LR receipts for customers on this trip"), 1), a("button", {
						type: "button",
						class: "font-medium text-primary-600 hover:underline",
						onClick: l[2] ||= (e) => C.value = !C.value
					}, x(C.value ? "Show trip customers only" : "Browse all customers"), 1)])) : r("", !0),
					E.value ? (_(), i("div", lt, "Loading…")) : p.value.length === 0 ? (_(), i("div", ut, [o.type === "lorry" && o.tripId && !C.value ? (_(), i("p", dt, [l[7] ||= s(" No lorry receipt found with matching bilty numbers for this trip's LRs. ", -1), a("button", {
						type: "button",
						class: "mt-1 block font-medium text-primary-600 hover:underline",
						onClick: l[3] ||= (e) => C.value = !0
					}, " Browse all unlinked lorry receipts instead → ")])) : o.type === "lr" && o.tripId && !C.value ? (_(), i("p", ft, [l[8] ||= s(" No more unassigned LR receipts found for the customers on this trip. ", -1), a("button", {
						type: "button",
						class: "mt-1 block font-medium text-primary-600 hover:underline",
						onClick: l[4] ||= (e) => C.value = !0
					}, " Browse all unlinked LR receipts instead → ")])) : (_(), i("p", pt, " No unlinked receipts. Create one first. "))])) : (_(), i("ul", mt, [(_(!0), i(e, null, y(p.value, (e) => (_(), i("li", { key: e.id }, [a("label", {
						class: f(["flex cursor-pointer items-center gap-3 rounded-lg border border-line-default bg-surface p-2.5 text-sm hover:bg-hover", { "border-primary-500": m.value.includes(e.id) }]),
						onClick: D((t) => A(e.id), ["prevent"])
					}, [
						a("input", {
							type: "checkbox",
							class: "accent-primary-600",
							checked: m.value.includes(e.id),
							onChange: (t) => A(e.id)
						}, null, 40, gt),
						a("span", _t, [a("span", vt, x(e.invoice_number) + " · " + x(e.from_city ?? "—") + " → " + x(e.to_city ?? "—"), 1), a("span", yt, x(e.customer_name ?? "No customer") + " · " + x(e.goods ?? ""), 1)]),
						a("span", bt, x(S(L)(e.amount)), 1)
					], 10, ht)]))), 128))])),
					m.value.length > 0 ? (_(), i("p", xt, " Selected: " + x(m.value.length) + " · " + x(S(L)(j.value)), 1)) : r("", !0),
					M.value ? (_(), i("p", St, " ⚠ Different routes selected — same vehicle? ")) : r("", !0)
				])]),
				footer: T(() => [a("div", Ct, [c(v, {
					variant: "primary-outline",
					size: "sm",
					onClick: l[5] ||= (e) => d("close")
				}, {
					default: T(() => [...l[9] ||= [s("Cancel", -1)]]),
					_: 1
				}), c(v, {
					variant: "primary",
					size: "sm",
					disabled: m.value.length === 0,
					onClick: N
				}, {
					default: T(() => [s(x(O.value ? `Link ${m.value.length}` : "Link"), 1)]),
					_: 1
				}, 8, ["disabled"])])]),
				_: 1
			}, 8, ["show"]);
		};
	}
}), Tt = {
	key: 0,
	class: "fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 print:p-0 print:bg-white"
}, Et = { class: "relative w-full max-w-2xl rounded-xl border border-line-default bg-surface p-6 shadow-2xl print:border-0 print:shadow-none print:max-w-none print:w-full print:p-8" }, Dt = { class: "mb-4 flex items-center justify-between border-b border-line-light pb-3 print:hidden" }, Ot = { class: "flex items-center gap-2" }, kt = { class: "text-xs text-muted" }, At = { class: "flex items-center gap-2" }, jt = { class: "voucher-box border-2 border-slate-800 p-5 font-sans text-slate-900 print:border-2 print:border-slate-800" }, Mt = { class: "mt-3 grid grid-cols-2 gap-4 text-xs border-b border-slate-300 pb-3" }, Nt = { class: "font-bold text-slate-900" }, Pt = { class: "mt-1" }, Ft = { class: "mt-1" }, It = { class: "font-semibold uppercase text-indigo-700" }, Lt = { class: "text-right" }, Rt = { class: "font-bold text-slate-900" }, zt = { class: "mt-1" }, Bt = { class: "font-bold uppercase text-slate-900" }, Vt = { class: "mt-1" }, Ht = { class: "mt-4 space-y-2 text-xs" }, Ut = { class: "flex items-start justify-between border-b border-dashed border-slate-200 py-1.5" }, Wt = { class: "font-bold text-slate-900 text-sm" }, Gt = { class: "flex items-start justify-between border-b border-dashed border-slate-200 py-1.5" }, Kt = { class: "font-medium text-slate-800" }, qt = {
	key: 0,
	class: "flex items-start justify-between border-b border-dashed border-slate-200 py-1.5"
}, Jt = { class: "font-mono font-medium text-slate-800" }, Yt = {
	key: 1,
	class: "flex items-start justify-between border-b border-dashed border-slate-200 py-1.5"
}, Xt = { class: "text-slate-800" }, Zt = { class: "my-5 flex items-center justify-between rounded border-2 border-slate-900 bg-slate-50 px-4 py-3" }, Qt = { class: "text-2xl font-extrabold text-slate-900" }, $t = /* @__PURE__ */ ((e, t) => {
	let n = e.__vccOpts || e;
	for (let [e, r] of t) n[e] = r;
	return n;
})(/* @__PURE__ */ l({
	__name: "TripPaymentVoucherModal",
	props: {
		show: { type: Boolean },
		payment: {},
		trip: {}
	},
	emits: ["close"],
	setup(e, { emit: n }) {
		let c = e, l = n, u = t(() => c.payment ? c.payment.type === "advance" ? "Advance Payment" : "Final Settlement Payment" : "Payment");
		function d() {
			window.print();
		}
		return (t, n) => e.show && e.payment && e.trip ? (_(), i("div", Tt, [a("div", Et, [a("div", Dt, [a("div", Ot, [n[1] ||= a("span", { class: "rounded bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700" }, " Payment Voucher ", -1), a("span", kt, "Voucher #" + x(e.payment.number ?? "—"), 1)]), a("div", At, [a("button", {
			type: "button",
			class: "inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-primary-700",
			onClick: d
		}, [...n[2] ||= [a("svg", {
			class: "h-4 w-4",
			fill: "none",
			viewBox: "0 0 24 24",
			stroke: "currentColor"
		}, [a("path", {
			"stroke-linecap": "round",
			"stroke-linejoin": "round",
			"stroke-width": "2",
			d: "M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
		})], -1), s(" Print Voucher ", -1)]]), a("button", {
			type: "button",
			class: "rounded-lg p-1.5 text-muted hover:bg-surface-secondary hover:text-heading",
			"aria-label": "Close",
			onClick: n[0] ||= (e) => l("close")
		}, [...n[3] ||= [a("svg", {
			class: "h-5 w-5",
			fill: "none",
			viewBox: "0 0 24 24",
			stroke: "currentColor"
		}, [a("path", {
			"stroke-linecap": "round",
			"stroke-linejoin": "round",
			"stroke-width": "2",
			d: "M6 18L18 6M6 6l12 12"
		})], -1)]])])]), a("div", jt, [
			n[19] ||= a("div", { class: "border-b-2 border-slate-800 pb-3 text-center" }, [a("h2", { class: "text-xl font-bold tracking-wide uppercase text-slate-900" }, "PAYMENT VOUCHER"), a("p", { class: "text-xs uppercase tracking-wider text-slate-600 font-medium" }, "Truck Hire & Freight Disbursement Memo")], -1),
			a("div", Mt, [a("div", null, [
				a("p", null, [
					n[4] ||= a("span", { class: "font-semibold text-slate-700" }, "Voucher No:", -1),
					n[5] ||= s(),
					a("span", Nt, x(e.payment.number ?? "—"), 1)
				]),
				a("p", Pt, [n[6] ||= a("span", { class: "font-semibold text-slate-700" }, "Payment Date:", -1), s(" " + x(e.payment.date ?? "—"), 1)]),
				a("p", Ft, [
					n[7] ||= a("span", { class: "font-semibold text-slate-700" }, "Payment Nature:", -1),
					n[8] ||= s(),
					a("span", It, x(u.value), 1)
				])
			]), a("div", Lt, [
				a("p", null, [
					n[9] ||= a("span", { class: "font-semibold text-slate-700" }, "Trip No:", -1),
					n[10] ||= s(),
					a("span", Rt, "#" + x(e.trip.trip_no), 1)
				]),
				a("p", zt, [
					n[11] ||= a("span", { class: "font-semibold text-slate-700" }, "Truck / Lorry No:", -1),
					n[12] ||= s(),
					a("span", Bt, x(e.payment.lorry_no || e.trip.lorry_no || "—"), 1)
				]),
				a("p", Vt, [n[13] ||= a("span", { class: "font-semibold text-slate-700" }, "Route:", -1), s(" " + x(e.trip.from_city ?? "—") + " → " + x(e.trip.to_city ?? "—"), 1)])
			])]),
			a("div", Ht, [
				a("div", Ut, [n[14] ||= a("span", { class: "font-semibold text-slate-700" }, "Paid To (Beneficiary):", -1), a("span", Wt, x(e.payment.pay_to ?? "Vehicle Owner / Supplier"), 1)]),
				a("div", Gt, [n[15] ||= a("span", { class: "font-semibold text-slate-700" }, "Payment Mode:", -1), a("span", Kt, x(e.payment.method ?? "Bank Transfer / NEFT"), 1)]),
				e.payment.reference ? (_(), i("div", qt, [n[16] ||= a("span", { class: "font-semibold text-slate-700" }, "Reference / UTR / Cheque No:", -1), a("span", Jt, x(e.payment.reference), 1)])) : r("", !0),
				e.payment.notes ? (_(), i("div", Yt, [n[17] ||= a("span", { class: "font-semibold text-slate-700" }, "Particulars / Remarks:", -1), a("span", Xt, x(e.payment.notes), 1)])) : r("", !0)
			]),
			a("div", Zt, [n[18] ||= a("span", { class: "text-xs font-bold uppercase tracking-wider text-slate-700" }, "Amount Paid", -1), a("span", Qt, x(S(L)(e.payment.amount)), 1)]),
			n[20] ||= o("<div class=\"mt-8 grid grid-cols-3 gap-4 pt-8 text-center text-xs\" data-v-c761222a><div class=\"border-t border-slate-400 pt-1\" data-v-c761222a><p class=\"font-medium text-slate-700\" data-v-c761222a>Prepared By</p></div><div class=\"border-t border-slate-400 pt-1\" data-v-c761222a><p class=\"font-medium text-slate-700\" data-v-c761222a>Checked / Verified</p></div><div class=\"border-t border-slate-400 pt-1\" data-v-c761222a><p class=\"font-medium text-slate-700\" data-v-c761222a>Receiver&#39;s Signature</p></div></div>", 1)
		])])])) : r("", !0);
	}
}), [["__scopeId", "data-v-c761222a"]]), en = { class: "min-h-screen bg-surface-secondary" }, tn = { class: "mx-auto max-w-5xl p-4 lg:p-6" }, nn = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, rn = { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, an = { class: "text-xl font-semibold text-heading" }, on = {
	key: 0,
	class: "inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-sm font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/20 align-middle ml-1.5",
	title: "Consolidated Multi-LR Load"
}, sn = {
	key: 1,
	class: "text-base font-normal text-muted"
}, cn = { class: "flex items-center gap-2" }, ln = {
	key: 0,
	class: "rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 ring-1 ring-inset ring-emerald-500/20"
}, un = {
	key: 1,
	class: "rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-600 ring-1 ring-inset ring-amber-500/20"
}, dn = {
	key: 2,
	class: "rounded-full bg-slate-500/10 px-2.5 py-1 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-500/20"
}, fn = {
	key: 3,
	class: "rounded-full bg-red-600 px-3 py-1 text-xs font-semibold text-white"
}, pn = ["disabled"], mn = ["value"], hn = {
	key: 0,
	class: "mb-4 rounded-lg bg-alert-error-bg p-3 text-sm text-alert-error-text"
}, gn = { class: "space-y-4" }, _n = { class: "space-y-2" }, vn = { class: "min-w-0" }, yn = { class: "font-medium text-heading" }, bn = { class: "truncate text-xs text-muted" }, xn = { class: "flex shrink-0 gap-1" }, Sn = {
	key: 0,
	class: "text-sm text-muted"
}, Cn = { class: "flex gap-2 pt-1" }, wn = { class: "mt-4 border-t border-line-light pt-3" }, Tn = { class: "mb-2 flex items-center justify-between" }, En = { class: "flex items-center gap-2" }, Dn = { class: "min-w-0" }, On = { class: "font-medium text-heading" }, kn = { class: "truncate text-xs text-muted" }, An = { class: "flex shrink-0 gap-1" }, jn = {
	key: 0,
	class: "text-sm text-muted"
}, Mn = {
	key: 0,
	class: "text-xs text-muted"
}, Nn = { class: "mt-4 border-t border-line-light pt-3" }, Pn = { class: "mb-2 flex items-center justify-between" }, Fn = { class: "flex items-center gap-2" }, In = { class: "min-w-0" }, Ln = { class: "font-medium text-heading" }, Rn = { class: "truncate text-xs text-muted" }, zn = { class: "flex shrink-0 gap-1" }, Bn = {
	key: 0,
	class: "text-sm text-muted"
}, Vn = {
	key: 0,
	class: "text-xs text-muted"
}, Hn = { class: "mt-4 border-t border-line-light pt-3" }, Un = { class: "mb-2 text-sm font-semibold text-heading" }, Wn = { class: "grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" }, Gn = { class: "mb-1.5 text-xs font-medium text-muted" }, Kn = {
	key: 0,
	class: "group relative flex h-28 items-center justify-center overflow-hidden rounded-lg border border-line-default bg-surface-secondary"
}, qn = ["src", "alt"], Jn = {
	key: 1,
	class: "flex flex-col items-center gap-1 text-subtle"
}, Yn = {
	key: 2,
	class: "flex items-center justify-center text-subtle"
}, Xn = { class: "absolute inset-0 flex items-center justify-center gap-1 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100" }, Zn = ["onClick"], Qn = ["disabled", "onClick"], $n = {
	key: 2,
	class: "mt-1 flex items-center justify-between gap-1"
}, er = { class: "min-w-0 truncate text-xs text-muted" }, tr = ["onClick"], nr = { class: "mt-4 border-t border-line-light pt-3" }, rr = { class: "grid grid-cols-2 gap-3 text-center sm:grid-cols-4" }, ir = { class: "rounded-xl border border-line-light bg-surface-secondary p-3" }, ar = { class: "mt-1 text-lg font-bold text-heading" }, or = { class: "mt-1 flex flex-wrap items-center justify-center gap-1 text-[11px]" }, sr = { class: "font-medium text-status-green" }, cr = {
	key: 0,
	class: "font-medium text-indigo-600"
}, lr = {
	key: 1,
	class: "font-medium text-status-red"
}, ur = { class: "rounded-xl border border-line-light bg-surface-secondary p-3" }, dr = { class: "mt-1 text-lg font-bold text-heading" }, fr = { class: "mt-1 text-[11px] text-muted" }, pr = { key: 0 }, mr = { class: "rounded-xl border border-line-light bg-surface-secondary p-3" }, hr = { class: "mt-1 text-lg font-bold text-heading" }, gr = { class: "rounded-xl border border-line-light bg-surface-secondary p-3" }, _r = { class: "mt-1 text-[11px] font-medium text-muted" }, vr = {
	key: 0,
	class: "mt-4"
}, yr = { class: "flex items-center justify-between gap-2" }, br = { class: "min-w-0" }, xr = { class: "font-medium text-heading" }, Sr = { class: "truncate text-xs text-muted" }, Cr = {
	key: 0,
	class: "mt-2.5 space-y-1.5 border-t border-line-light pt-2"
}, wr = { class: "flex items-center gap-2" }, Tr = { class: "font-medium text-heading" }, Er = { class: "text-muted" }, Dr = { class: "flex items-center gap-3" }, Or = { class: "text-right" }, kr = { class: "font-bold text-heading" }, Ar = {
	key: 0,
	class: "block text-[10px] text-muted"
}, jr = ["onClick"], Mr = {
	key: 1,
	class: "mt-4"
}, Nr = { class: "flex items-center justify-between gap-2" }, Pr = { class: "min-w-0" }, Fr = { class: "font-medium text-heading" }, Ir = {
	key: 0,
	class: "text-xs font-normal text-muted"
}, Lr = { class: "truncate text-xs text-muted" }, Rr = { key: 0 }, zr = {
	key: 0,
	class: "mt-1 text-xs text-muted"
}, Br = { class: "font-semibold text-heading" }, Vr = {
	key: 1,
	class: "mt-2.5 space-y-1.5 border-t border-line-light pt-2"
}, Hr = { class: "flex items-center gap-2" }, Ur = { class: "text-muted" }, Wr = { key: 0 }, Gr = { class: "flex items-center gap-3" }, Kr = { class: "font-bold text-heading" }, qr = ["onClick"], Jr = { class: "mt-3 flex items-center justify-between rounded-lg border border-line-light bg-surface-secondary p-2.5 text-xs" }, Yr = { class: "text-muted" }, Xr = { class: "text-heading" }, Zr = /* @__PURE__ */ l({
	__name: "TripDetailPage",
	props: {
		id: {},
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(o) {
		let l = o, u = v(null), d = v(!0), m = v([]), D = v(!1), O = v(!1), k = v(!1), A = v(!1), M = v(!1), N = v(null), ee = v(null), te = v(""), ne = t(() => Number(l.id)), P = t({
			get: () => u.value?.status.id ?? null,
			set: (e) => {
				e !== null && ve(e);
			}
		}), re = t(() => u.value?.receipts.filter((e) => e.type === "lr") ?? []), ie = t(() => u.value?.receipts.filter((e) => e.type === "lorry") ?? []), ae = t(() => u.value?.receipts.filter((e) => e.type === "invoice") ?? []), oe = v(!1), se = v(null);
		function ce(e) {
			se.value = e, oe.value = !0;
		}
		function le(e) {
			e && window.open("/payments/pdf/" + e, "_blank");
		}
		let ue = [
			{
				role: "consignment",
				label: "Consignment & Proof of Delivery",
				slots: [{
					key: "pod",
					label: "Signed Delivery Copy / POD"
				}]
			},
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
		function de(e) {
			return u.value?.documents.find((t) => t.category === e) ?? null;
		}
		let fe = v({});
		function pe() {
			for (let e of Object.values(fe.value)) URL.revokeObjectURL(e);
			fe.value = {};
		}
		async function me() {
			if (u.value) {
				pe();
				for (let e of u.value.documents) try {
					let t = await We(l.client, u.value.id, e.id);
					fe.value[e.id] = URL.createObjectURL(t);
				} catch {}
			}
		}
		function he(e) {
			let t = fe.value[e];
			t && window.open(t, "_blank", "noopener");
		}
		w(() => u.value?.documents, () => {
			me();
		});
		async function ge() {
			d.value = !0;
			try {
				u.value = await Ne(l.client, ne.value);
			} catch (e) {
				l.notify("error", I(e, "Unable to load the trip."));
			} finally {
				d.value = !1;
			}
		}
		async function _e() {
			try {
				m.value = await Me(l.client);
			} catch {}
		}
		async function ve(e) {
			if (!(!u.value || D.value)) {
				D.value = !0;
				try {
					u.value = await Le(l.client, u.value.id, e), l.notify("success", "Status updated.");
				} catch (e) {
					l.notify("error", I(e, "Unable to change the status."));
				} finally {
					D.value = !1;
				}
			}
		}
		function ye() {
			document.hidden || ge();
		}
		h(() => {
			ge(), _e(), document.addEventListener("visibilitychange", ye);
		}), g(() => {
			pe(), document.removeEventListener("visibilitychange", ye);
		});
		async function be() {
			if (!(!u.value || !window.confirm("Cancel this trip?"))) try {
				u.value = await Ie(l.client, u.value.id), l.notify("success", "Trip cancelled.");
			} catch (e) {
				l.notify("error", I(e, "Unable to cancel the trip."));
			}
		}
		async function xe(e) {
			if (u.value) {
				O.value = !1;
				try {
					for (let t of e) u.value = await ze(l.client, u.value.id, t, "lr");
					l.notify("success", "LR receipt linked.");
				} catch (e) {
					l.notify("error", I(e, "Unable to link the receipt."));
				}
			}
		}
		async function Se(e) {
			if (!(!u.value || e.length === 0)) {
				k.value = !1;
				try {
					for (let t of e) u.value = await ze(l.client, u.value.id, t, "lorry");
					l.notify("success", "Lorry receipt linked.");
				} catch (e) {
					l.notify("error", I(e, "Unable to link the receipt."));
				}
			}
		}
		async function Ce() {
			if (u.value) {
				M.value = !0;
				try {
					u.value = await Be(l.client, u.value.id), l.notify("success", "Lorry receipt(s) fetched and linked.");
				} catch (e) {
					l.notify("error", I(e, "No lorry receipt found with matching bilty numbers for these LRs."));
				} finally {
					M.value = !1;
				}
			}
		}
		async function we(e) {
			if (u.value) try {
				u.value = await Ge(l.client, u.value.id, e), l.notify("success", "Receipt unlinked.");
			} catch (e) {
				l.notify("error", I(e, "Unable to unlink the receipt."));
			}
		}
		async function Te() {
			if (u.value) {
				A.value = !0;
				try {
					u.value = await Ve(l.client, u.value.id), l.notify("success", "Invoice receipt fetched and linked.");
				} catch (e) {
					l.notify("error", I(e, "No invoice receipt found for these LR numbers."));
				} finally {
					A.value = !1;
				}
			}
		}
		function Ee(e) {
			te.value = e, ee.value?.click();
		}
		async function De(e) {
			if (!u.value) return;
			let t = e.target, n = t.files;
			if (!n || n.length === 0) return;
			let r = te.value;
			N.value = r;
			try {
				u.value = await He(l.client, u.value.id, n[0], r), l.notify("success", "Document uploaded.");
			} catch (e) {
				l.notify("error", I(e, "Unable to upload the document."));
			} finally {
				N.value = null, t.value = "";
			}
		}
		async function Oe(e) {
			if (u.value) try {
				u.value = await Ue(l.client, u.value.id, e), l.notify("success", "Document removed.");
			} catch (e) {
				l.notify("error", I(e, "Unable to remove the document."));
			}
		}
		function ke(e) {
			l.router?.push(j.invoiceView(e));
		}
		function Ae() {
			l.router?.push(j.board);
		}
		return (t, l) => {
			let h = b("BaseButton"), g = b("BaseSettingCard");
			return _(), i("div", en, [
				a("div", tn, [d.value ? (_(), i("div", nn, "Loading the trip…")) : u.value ? (_(), i(e, { key: 1 }, [
					a("div", rn, [a("div", null, [a("button", {
						type: "button",
						class: "text-sm text-muted hover:text-heading",
						onClick: Ae
					}, " ← Trips "), a("h1", an, [
						s(" Trip #" + x(u.value.trip_no) + " ", 1),
						u.value.lr_numbers && u.value.lr_numbers.length > 1 ? (_(), i("span", on, " LR: " + x(u.value.lr_numbers.join(", ")), 1)) : u.value.lr_numbers && u.value.lr_numbers.length === 1 && String(u.value.trip_no) !== String(u.value.lr_numbers[0]) ? (_(), i("span", sn, " [LR: " + x(u.value.lr_numbers[0]) + "] ", 1)) : r("", !0),
						s(" · " + x(u.value.from_city ?? "—") + " → " + x(u.value.to_city ?? "—"), 1)
					])]), a("div", cn, [
						u.value.money?.payment_status === "PAID" ? (_(), i("span", ln, " ● Payment Received ")) : u.value.money?.payment_status === "PARTIALLY_PAID" ? (_(), i("span", un, " ◐ Partially Paid ")) : u.value.money?.payment_status ? (_(), i("span", dn, " ○ Payment Pending ")) : r("", !0),
						u.value.cancelled_at ? (_(), i("span", fn, " Cancelled ")) : m.value.length > 0 ? E((_(), i("select", {
							key: 4,
							"onUpdate:modelValue": l[0] ||= (e) => P.value = e,
							disabled: D.value,
							class: "cursor-pointer rounded-full border-0 px-3 py-1 text-xs font-semibold text-white outline-none",
							style: p({ backgroundColor: u.value.status.colour ?? "#94a3b8" })
						}, [(_(!0), i(e, null, y(m.value, (e) => (_(), i("option", {
							key: e.id,
							value: e.id,
							class: "bg-surface text-body"
						}, x(e.name), 9, mn))), 128))], 12, pn)), [[C, P.value]]) : (_(), i("span", {
							key: 5,
							class: "rounded-full px-3 py-1 text-xs font-semibold text-white",
							style: p({ backgroundColor: u.value.status.colour ?? "#94a3b8" })
						}, x(u.value.status.name), 5)),
						u.value.cancelled_at ? r("", !0) : (_(), n(h, {
							key: 6,
							size: "sm",
							variant: "danger",
							onClick: be
						}, {
							default: T(() => [...l[6] ||= [s(" Cancel Trip ", -1)]]),
							_: 1
						}))
					])]),
					u.value.cancelled_at ? (_(), i("div", hn, " This trip is cancelled. ")) : r("", !0),
					a("div", gn, [c(g, {
						title: "Documents",
						description: "LR receipts, lorry receipt, invoice receipt and trip documents for this trip."
					}, {
						default: T(() => [
							a("div", _n, [
								(_(!0), i(e, null, y(re.value, (e) => (_(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", vn, [a("p", yn, "📄 " + x(e.invoice_number), 1), a("p", bn, x(e.customer_name ?? "No customer") + " · " + x(S(L)(e.amount)), 1)]), a("div", xn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => ke(e.invoice_id)
								}, {
									default: T(() => [...l[7] ||= [s(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (_(), n(h, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => we(e.id)
								}, {
									default: T(() => [...l[8] ||= [s(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								re.value.length === 0 ? (_(), i("p", Sn, "No LR receipts linked yet.")) : r("", !0),
								a("div", Cn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: l[1] ||= (e) => O.value = !0
								}, {
									default: T(() => [...l[9] ||= [s(" + Link LR ", -1)]]),
									_: 1
								})])
							]),
							a("div", wn, [
								a("div", Tn, [l[11] ||= a("p", { class: "text-xs font-semibold uppercase tracking-wide text-muted" }, "Lorry Receipt", -1), a("div", En, [c(h, {
									size: "sm",
									variant: "primary-outline",
									disabled: M.value || re.value.length === 0,
									onClick: Ce
								}, {
									default: T(() => [s(x(M.value ? "Fetching…" : "Fetch Lorry Receipt"), 1)]),
									_: 1
								}, 8, ["disabled"]), c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: l[2] ||= (e) => k.value = !0
								}, {
									default: T(() => [...l[10] ||= [s(" + Link Lorry Receipt ", -1)]]),
									_: 1
								})])]),
								(_(!0), i(e, null, y(ie.value, (e) => (_(), i("div", {
									key: e.id,
									class: "mb-2 flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", Dn, [a("p", On, "🚛 " + x(e.invoice_number), 1), a("p", kn, x(e.customer_name ?? "No customer") + " · " + x(S(L)(e.amount)), 1)]), a("div", An, [c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => ke(e.invoice_id)
								}, {
									default: T(() => [...l[12] ||= [s(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (_(), n(h, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => we(e.id)
								}, {
									default: T(() => [...l[13] ||= [s(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								ie.value.length === 0 ? (_(), i("p", jn, [l[14] ||= s(" No lorry receipt linked yet. ", -1), re.value.length === 0 ? (_(), i("span", Mn, " (Link an LR first)")) : r("", !0)])) : r("", !0)
							]),
							a("div", Nn, [
								a("div", Pn, [l[15] ||= a("p", { class: "text-xs font-semibold uppercase tracking-wide text-muted" }, "Invoice Receipt", -1), a("div", Fn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									disabled: A.value || re.value.length === 0,
									onClick: Te
								}, {
									default: T(() => [s(x(A.value ? "Fetching…" : "Fetch Invoice Receipt"), 1)]),
									_: 1
								}, 8, ["disabled"])])]),
								(_(!0), i(e, null, y(ae.value, (e) => (_(), i("div", {
									key: e.id,
									class: "mb-2 flex items-center justify-between gap-2 rounded-lg border border-line-light p-2.5 text-sm"
								}, [a("div", In, [a("p", Ln, "🧾 " + x(e.invoice_number), 1), a("p", Rn, x(e.customer_name ?? "No customer") + " · " + x(S(L)(e.amount)), 1)]), a("div", zn, [c(h, {
									size: "sm",
									variant: "primary-outline",
									onClick: (t) => ke(e.invoice_id)
								}, {
									default: T(() => [...l[16] ||= [s(" View ", -1)]]),
									_: 1
								}, 8, ["onClick"]), e.billed_invoice_id ? r("", !0) : (_(), n(h, {
									key: 0,
									size: "sm",
									variant: "danger-outline",
									onClick: (t) => we(e.id)
								}, {
									default: T(() => [...l[17] ||= [s(" Unlink ", -1)]]),
									_: 1
								}, 8, ["onClick"]))])]))), 128)),
								ae.value.length === 0 ? (_(), i("p", Bn, [l[18] ||= s(" No invoice receipt linked yet. ", -1), re.value.length === 0 ? (_(), i("span", Vn, " (Link an LR first)")) : r("", !0)])) : r("", !0)
							]),
							a("div", Hn, [
								l[27] ||= a("p", { class: "mb-3 text-xs font-semibold uppercase tracking-wide text-muted" }, "Trip Documents", -1),
								a("input", {
									ref_key: "podFileInput",
									ref: ee,
									type: "file",
									class: "hidden",
									accept: "image/*,application/pdf",
									onChange: De
								}, null, 544),
								(_(), i(e, null, y(ue, (t) => a("div", {
									key: t.role,
									class: "mb-4 last:mb-0"
								}, [a("p", Un, x(t.label), 1), a("div", Wn, [(_(!0), i(e, null, y(t.slots, (t) => (_(), i("div", {
									key: t.key,
									class: "flex flex-col"
								}, [
									a("p", Gn, x(t.label), 1),
									de(t.key) ? (_(), i("div", Kn, [de(t.key).is_image && fe.value[de(t.key).id] ? (_(), i("img", {
										key: 0,
										src: fe.value[de(t.key).id],
										alt: t.label,
										class: "h-full w-full object-cover"
									}, null, 8, qn)) : de(t.key).is_image ? (_(), i("div", Yn, [...l[20] ||= [a("svg", {
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
									})], -1)]])) : (_(), i("div", Jn, [...l[19] ||= [a("svg", {
										class: "h-8 w-8",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "1.25",
										d: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
									})], -1), a("span", { class: "text-xs" }, "PDF", -1)]])), a("div", Xn, [c(h, {
										size: "sm",
										variant: "primary",
										onClick: (e) => he(de(t.key).id)
									}, {
										default: T(() => [...l[21] ||= [s(" View ", -1)]]),
										_: 1
									}, 8, ["onClick"]), a("button", {
										type: "button",
										class: "flex h-7 w-7 items-center justify-center rounded-full bg-surface text-status-red shadow",
										"aria-label": "Remove",
										onClick: (e) => Oe(de(t.key).id)
									}, [...l[22] ||= [a("svg", {
										class: "h-4 w-4",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "2",
										d: "M6 18L18 6M6 6l12 12"
									})], -1)]], 8, Zn)])])) : (_(), i("button", {
										key: 1,
										type: "button",
										class: "flex h-28 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-line-strong bg-surface-secondary text-subtle transition-colors hover:border-primary-400 hover:text-primary-500",
										disabled: N.value === t.key,
										onClick: (e) => Ee(t.key)
									}, [N.value === t.key ? (_(), i(e, { key: 0 }, [l[23] ||= a("svg", {
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
									})], -1), l[24] ||= a("span", { class: "text-xs" }, "Uploading…", -1)], 64)) : (_(), i(e, { key: 1 }, [l[25] ||= a("svg", {
										class: "h-6 w-6",
										fill: "none",
										viewBox: "0 0 24 24",
										stroke: "currentColor"
									}, [a("path", {
										"stroke-linecap": "round",
										"stroke-linejoin": "round",
										"stroke-width": "1.5",
										d: "M12 4v16m8-8H4"
									})], -1), l[26] ||= a("span", { class: "text-xs" }, "Upload", -1)], 64))], 8, Qn)),
									de(t.key) ? (_(), i("div", $n, [a("span", er, x(de(t.key).file_name), 1), a("button", {
										type: "button",
										class: "shrink-0 text-xs text-primary-500 hover:underline",
										onClick: (e) => Ee(t.key)
									}, " Replace ", 8, tr)])) : r("", !0)
								]))), 128))])])), 64))
							]),
							a("div", nr, [
								l[36] ||= a("p", { class: "mb-3 text-xs font-semibold uppercase tracking-wide text-muted" }, "Trip Financial Summary & Payments", -1),
								a("div", rr, [
									a("div", ir, [
										l[28] ||= a("p", { class: "text-xs font-medium text-muted" }, "Customer Billed (Revenue)", -1),
										a("p", ar, x(u.value.money.revenue > 0 ? S(L)(u.value.money.revenue) : "—"), 1),
										a("div", or, [
											a("span", sr, "Recv: " + x(S(L)(u.value.money.actual_revenue ?? 0)), 1),
											(u.value.money.tds_amount ?? 0) > 0 ? (_(), i("span", cr, "| TDS: " + x(S(L)(u.value.money.tds_amount)), 1)) : r("", !0),
											(u.value.money.customer_due ?? 0) > 0 ? (_(), i("span", lr, "| Due: " + x(S(L)(u.value.money.customer_due)), 1)) : r("", !0)
										])
									]),
									a("div", ur, [
										l[29] ||= a("p", { class: "text-xs font-medium text-muted" }, "Truck Hire & Costs", -1),
										a("p", dr, x(S(L)(u.value.money.cost)), 1),
										a("p", fr, [s(" Hire: " + x(S(L)(u.value.money.cost - u.value.expenses.reduce((e, t) => e + t.amount, 0))) + " ", 1), u.value.expenses.length > 0 ? (_(), i("span", pr, "· Exp: " + x(S(L)(u.value.expenses.reduce((e, t) => e + t.amount, 0))), 1)) : r("", !0)])
									]),
									a("div", mr, [
										l[30] ||= a("p", { class: "text-xs font-medium text-muted" }, "Paid to Truck Owner", -1),
										a("p", hr, x(S(L)(u.value.money.owner_paid)), 1),
										a("p", { class: f(["mt-1 text-[11px] font-semibold", u.value.money.owner_balance > 0 ? "text-amber-600" : "text-status-green"]) }, x(u.value.money.owner_balance > 0 ? `Balance Due: ${S(L)(u.value.money.owner_balance)}` : "✓ All Settled"), 3)
									]),
									a("div", gr, [
										l[31] ||= a("p", { class: "text-xs font-medium text-muted" }, "Net Trip Profit", -1),
										a("p", { class: f(["mt-1 text-lg font-extrabold", u.value.money.profit >= 0 ? "text-status-green" : "text-status-red"]) }, x(S(L)(u.value.money.profit)), 3),
										a("p", _r, x(u.value.money.revenue > 0 ? "Margin: " + (u.value.money.profit / u.value.money.revenue * 100).toFixed(1) + "%" : "Margin: —"), 1)
									])
								]),
								u.value.money.receivable.length > 0 ? (_(), i("div", vr, [l[32] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "Customer Collections (Money In)", -1), (_(!0), i(e, null, y(u.value.money.receivable, (t) => (_(), i("div", {
									key: t.invoice_id,
									class: "mb-2 rounded-lg border border-line-light p-3 text-sm"
								}, [a("div", yr, [a("div", br, [a("p", xr, "🧾 Invoice #" + x(t.invoice_number), 1), a("p", Sr, x(t.customer_name ?? "No customer") + " · Billed " + x(S(L)(t.total)) + " · Due " + x(S(L)(t.due_amount)), 1)]), a("span", { class: f(["shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold", {
									"bg-status-red/10 text-status-red": t.paid_status === "UNPAID",
									"bg-status-yellow/10 text-status-yellow": t.paid_status === "PARTIALLY_PAID",
									"bg-status-green/10 text-status-green": t.paid_status === "PAID"
								}]) }, x(t.paid_status), 3)]), t.payments.length > 0 ? (_(), i("div", Cr, [(_(!0), i(e, null, y(t.payments, (e) => (_(), i("div", {
									key: e.id,
									class: "flex items-center justify-between gap-2 rounded-md bg-surface-secondary px-2.5 py-1.5 text-xs"
								}, [a("div", wr, [a("span", Tr, "💰 " + x(e.number ?? "Payment"), 1), a("span", Er, x(e.date ?? "—") + " · " + x(e.method ?? "Bank"), 1)]), a("div", Dr, [a("div", Or, [a("span", kr, x(S(L)(e.amount + (e.tds_amount ?? 0))), 1), (e.tds_amount ?? 0) > 0 ? (_(), i("span", Ar, " Bank: " + x(S(L)(e.amount)) + " | TDS: " + x(S(L)(e.tds_amount)), 1)) : r("", !0)]), e.unique_hash ? (_(), i("button", {
									key: 0,
									type: "button",
									class: "rounded border border-primary-300 bg-primary-50 px-2 py-0.5 text-[11px] font-medium text-primary-700 hover:bg-primary-100",
									onClick: (t) => le(e.unique_hash)
								}, " 📄 Payment Receipt ", 8, jr)) : r("", !0)])]))), 128))])) : r("", !0)]))), 128))])) : r("", !0),
								u.value.money.payable.length > 0 ? (_(), i("div", Mr, [l[33] ||= a("p", { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, "Truck Owner Hire & Payment Vouchers (Money Out)", -1), (_(!0), i(e, null, y(u.value.money.payable, (t) => (_(), i("div", {
									key: t.invoice_number,
									class: "mb-2 rounded-lg border border-line-light p-3 text-sm"
								}, [
									a("div", Nr, [a("div", Pr, [a("p", Fr, [s("🚛 Lorry Challan #" + x(t.invoice_number) + " ", 1), t.bill_number ? (_(), i("span", Ir, "(Bill #" + x(t.bill_number) + ")", 1)) : r("", !0)]), a("p", Lr, [s(x(t.supplier_name ?? "Truck Owner / Supplier"), 1), t.lorry_no ? (_(), i("span", Rr, " · Vehicle: " + x(t.lorry_no), 1)) : r("", !0)])]), t.bill_status ? (_(), i("span", {
										key: 0,
										class: f(["shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold", {
											"bg-status-red/10 text-status-red": t.bill_status === "UNPAID",
											"bg-status-yellow/10 text-status-yellow": t.bill_status === "PARTIAL",
											"bg-status-green/10 text-status-green": t.bill_status === "SETTLED"
										}])
									}, x(t.bill_status), 3)) : r("", !0)]),
									t.bill_total === null ? r("", !0) : (_(), i("p", zr, [s(" Total Hire " + x(S(L)(t.bill_total)) + " · Balance Due ", 1), a("span", Br, x(S(L)(t.bill_due ?? 0)), 1)])),
									t.payments.length > 0 ? (_(), i("div", Vr, [(_(!0), i(e, null, y(t.payments, (e) => (_(), i("div", {
										key: e.id,
										class: "flex items-center justify-between gap-2 rounded-md bg-surface-secondary px-2.5 py-1.5 text-xs"
									}, [a("div", Hr, [a("span", { class: f(["rounded px-1.5 py-0.5 text-[10px] font-bold uppercase", e.type === "advance" ? "bg-blue-100 text-blue-700" : "bg-emerald-100 text-emerald-700"]) }, x(e.type === "advance" ? "Advance" : "Final Settlement"), 3), a("span", Ur, [s(x(e.date ?? "—"), 1), e.reference ? (_(), i("span", Wr, " · Ref: " + x(e.reference), 1)) : r("", !0)])]), a("div", Gr, [a("span", Kr, x(S(L)(e.amount)), 1), a("button", {
										type: "button",
										class: "inline-flex items-center gap-1 rounded border border-indigo-300 bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 shadow-sm hover:bg-indigo-100",
										onClick: (t) => ce(e)
									}, " 🎫 View Voucher ", 8, qr)])]))), 128))])) : r("", !0)
								]))), 128))])) : r("", !0),
								a("div", Jr, [a("span", Yr, [
									l[34] ||= s(" Total Paid to Owner: ", -1),
									a("strong", Xr, x(S(L)(u.value.money.owner_paid)), 1),
									l[35] ||= s(" · Remaining Balance: ", -1),
									a("strong", { class: f(u.value.money.owner_balance > 0 ? "text-amber-600" : "text-status-green") }, x(S(L)(u.value.money.owner_balance)), 3)
								]), u.value.money.bill_status ? (_(), i("span", {
									key: 0,
									class: f(["rounded-full px-2 py-0.5 text-[11px] font-semibold", {
										"bg-status-red/10 text-status-red": u.value.money.bill_status === "UNPAID",
										"bg-status-yellow/10 text-status-yellow": u.value.money.bill_status === "PARTIAL",
										"bg-status-green/10 text-status-green": u.value.money.bill_status === "SETTLED"
									}])
								}, x(u.value.money.bill_status), 3)) : r("", !0)])
							])
						]),
						_: 1
					})])
				], 64)) : r("", !0)]),
				c(wt, {
					show: O.value,
					client: o.client,
					notify: o.notify,
					type: "lr",
					multiple: "",
					"trip-id": u.value?.id,
					onClose: l[3] ||= (e) => O.value = !1,
					onSelect: xe
				}, null, 8, [
					"show",
					"client",
					"notify",
					"trip-id"
				]),
				c(wt, {
					show: k.value,
					client: o.client,
					notify: o.notify,
					type: "lorry",
					multiple: "",
					"trip-id": u.value?.id,
					onClose: l[4] ||= (e) => k.value = !1,
					onSelect: Se
				}, null, 8, [
					"show",
					"client",
					"notify",
					"trip-id"
				]),
				c($t, {
					show: oe.value,
					payment: se.value,
					trip: u.value,
					onClose: l[5] ||= (e) => oe.value = !1
				}, null, 8, [
					"show",
					"payment",
					"trip"
				])
			]);
		};
	}
});
//#endregion
//#region ../../node_modules/sortablejs/modular/sortable.esm.js
function Qr(e, t) {
	var n = Object.keys(e);
	if (Object.getOwnPropertySymbols) {
		var r = Object.getOwnPropertySymbols(e);
		t && (r = r.filter(function(t) {
			return Object.getOwnPropertyDescriptor(e, t).enumerable;
		})), n.push.apply(n, r);
	}
	return n;
}
function $r(e) {
	for (var t = 1; t < arguments.length; t++) {
		var n = arguments[t] == null ? {} : arguments[t];
		t % 2 ? Qr(Object(n), !0).forEach(function(t) {
			ti(e, t, n[t]);
		}) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(n)) : Qr(Object(n)).forEach(function(t) {
			Object.defineProperty(e, t, Object.getOwnPropertyDescriptor(n, t));
		});
	}
	return e;
}
function ei(e) {
	"@babel/helpers - typeof";
	return ei = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function(e) {
		return typeof e;
	} : function(e) {
		return e && typeof Symbol == "function" && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e;
	}, ei(e);
}
function ti(e, t, n) {
	return t in e ? Object.defineProperty(e, t, {
		value: n,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[t] = n, e;
}
function ni() {
	return ni = Object.assign || function(e) {
		for (var t = 1; t < arguments.length; t++) {
			var n = arguments[t];
			for (var r in n) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
		}
		return e;
	}, ni.apply(this, arguments);
}
function ri(e, t) {
	if (e == null) return {};
	var n = {}, r = Object.keys(e), i, a;
	for (a = 0; a < r.length; a++) i = r[a], !(t.indexOf(i) >= 0) && (n[i] = e[i]);
	return n;
}
function ii(e, t) {
	if (e == null) return {};
	var n = ri(e, t), r, i;
	if (Object.getOwnPropertySymbols) {
		var a = Object.getOwnPropertySymbols(e);
		for (i = 0; i < a.length; i++) r = a[i], !(t.indexOf(r) >= 0) && Object.prototype.propertyIsEnumerable.call(e, r) && (n[r] = e[r]);
	}
	return n;
}
var ai = "1.14.0";
function oi(e) {
	if (typeof window < "u" && window.navigator) return !!/* @__PURE__ */ navigator.userAgent.match(e);
}
var si = oi(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i), ci = oi(/Edge/i), li = oi(/firefox/i), ui = oi(/safari/i) && !oi(/chrome/i) && !oi(/android/i), di = oi(/iP(ad|od|hone)/i), fi = oi(/chrome/i) && oi(/android/i), pi = {
	capture: !1,
	passive: !1
};
function R(e, t, n) {
	e.addEventListener(t, n, !si && pi);
}
function z(e, t, n) {
	e.removeEventListener(t, n, !si && pi);
}
function mi(e, t) {
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
function hi(e) {
	return e.host && e !== document && e.host.nodeType ? e.host : e.parentNode;
}
function gi(e, t, n, r) {
	if (e) {
		n ||= document;
		do {
			if (t != null && (t[0] === ">" ? e.parentNode === n && mi(e, t) : mi(e, t)) || r && e === n) return e;
			if (e === n) break;
		} while (e = hi(e));
	}
	return null;
}
var _i = /\s+/g;
function B(e, t, n) {
	e && t && (e.classList ? e.classList[n ? "add" : "remove"](t) : e.className = ((" " + e.className + " ").replace(_i, " ").replace(" " + t + " ", " ") + (n ? " " + t : "")).replace(_i, " "));
}
function V(e, t, n) {
	var r = e && e.style;
	if (r) {
		if (n === void 0) return document.defaultView && document.defaultView.getComputedStyle ? n = document.defaultView.getComputedStyle(e, "") : e.currentStyle && (n = e.currentStyle), t === void 0 ? n : n[t];
		!(t in r) && t.indexOf("webkit") === -1 && (t = "-webkit-" + t), r[t] = n + (typeof n == "string" ? "" : "px");
	}
}
function vi(e, t) {
	var n = "";
	if (typeof e == "string") n = e;
	else do {
		var r = V(e, "transform");
		r && r !== "none" && (n = r + " " + n);
	} while (!t && (e = e.parentNode));
	var i = window.DOMMatrix || window.WebKitCSSMatrix || window.CSSMatrix || window.MSCSSMatrix;
	return i && new i(n);
}
function yi(e, t, n) {
	if (e) {
		var r = e.getElementsByTagName(t), i = 0, a = r.length;
		if (n) for (; i < a; i++) n(r[i], i);
		return r;
	}
	return [];
}
function bi() {
	return document.scrollingElement || document.documentElement;
}
function H(e, t, n, r, i) {
	if (!(!e.getBoundingClientRect && e !== window)) {
		var a, o, s, c, l, u, d;
		if (e !== window && e.parentNode && e !== bi() ? (a = e.getBoundingClientRect(), o = a.top, s = a.left, c = a.bottom, l = a.right, u = a.height, d = a.width) : (o = 0, s = 0, c = window.innerHeight, l = window.innerWidth, u = window.innerHeight, d = window.innerWidth), (t || n) && e !== window && (i ||= e.parentNode, !si)) do
			if (i && i.getBoundingClientRect && (V(i, "transform") !== "none" || n && V(i, "position") !== "static")) {
				var f = i.getBoundingClientRect();
				o -= f.top + parseInt(V(i, "border-top-width")), s -= f.left + parseInt(V(i, "border-left-width")), c = o + a.height, l = s + a.width;
				break;
			}
		while (i = i.parentNode);
		if (r && e !== window) {
			var p = vi(i || e), m = p && p.a, h = p && p.d;
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
function xi(e, t, n) {
	for (var r = Di(e, !0), i = H(e)[t]; r;) {
		var a = H(r)[n], o = void 0;
		if (o = n === "top" || n === "left" ? i >= a : i <= a, !o) return r;
		if (r === bi()) break;
		r = Di(r, !1);
	}
	return !1;
}
function Si(e, t, n, r) {
	for (var i = 0, a = 0, o = e.children; a < o.length;) {
		if (o[a].style.display !== "none" && o[a] !== Q.ghost && (r || o[a] !== Q.dragged) && gi(o[a], n.draggable, e, !1)) {
			if (i === t) return o[a];
			i++;
		}
		a++;
	}
	return null;
}
function Ci(e, t) {
	for (var n = e.lastElementChild; n && (n === Q.ghost || V(n, "display") === "none" || t && !mi(n, t));) n = n.previousElementSibling;
	return n || null;
}
function wi(e, t) {
	var n = 0;
	if (!e || !e.parentNode) return -1;
	for (; e = e.previousElementSibling;) e.nodeName.toUpperCase() !== "TEMPLATE" && e !== Q.clone && (!t || mi(e, t)) && n++;
	return n;
}
function Ti(e) {
	var t = 0, n = 0, r = bi();
	if (e) do {
		var i = vi(e), a = i.a, o = i.d;
		t += e.scrollLeft * a, n += e.scrollTop * o;
	} while (e !== r && (e = e.parentNode));
	return [t, n];
}
function Ei(e, t) {
	for (var n in e) if (e.hasOwnProperty(n)) {
		for (var r in t) if (t.hasOwnProperty(r) && t[r] === e[n][r]) return Number(n);
	}
	return -1;
}
function Di(e, t) {
	if (!e || !e.getBoundingClientRect) return bi();
	var n = e, r = !1;
	do
		if (n.clientWidth < n.scrollWidth || n.clientHeight < n.scrollHeight) {
			var i = V(n);
			if (n.clientWidth < n.scrollWidth && (i.overflowX == "auto" || i.overflowX == "scroll") || n.clientHeight < n.scrollHeight && (i.overflowY == "auto" || i.overflowY == "scroll")) {
				if (!n.getBoundingClientRect || n === document.body) return bi();
				if (r || t) return n;
				r = !0;
			}
		}
	while (n = n.parentNode);
	return bi();
}
function Oi(e, t) {
	if (e && t) for (var n in t) t.hasOwnProperty(n) && (e[n] = t[n]);
	return e;
}
function ki(e, t) {
	return Math.round(e.top) === Math.round(t.top) && Math.round(e.left) === Math.round(t.left) && Math.round(e.height) === Math.round(t.height) && Math.round(e.width) === Math.round(t.width);
}
var Ai;
function ji(e, t) {
	return function() {
		if (!Ai) {
			var n = arguments, r = this;
			n.length === 1 ? e.call(r, n[0]) : e.apply(r, n), Ai = setTimeout(function() {
				Ai = void 0;
			}, t);
		}
	};
}
function Mi() {
	clearTimeout(Ai), Ai = void 0;
}
function Ni(e, t, n) {
	e.scrollLeft += t, e.scrollTop += n;
}
function Pi(e) {
	var t = window.Polymer, n = window.jQuery || window.Zepto;
	return t && t.dom ? t.dom(e).cloneNode(!0) : n ? n(e).clone(!0)[0] : e.cloneNode(!0);
}
var Fi = "Sortable" + (/* @__PURE__ */ new Date()).getTime();
function Ii() {
	var e = [], t;
	return {
		captureAnimationState: function() {
			e = [], this.options.animation && [].slice.call(this.el.children).forEach(function(t) {
				if (!(V(t, "display") === "none" || t === Q.ghost)) {
					e.push({
						target: t,
						rect: H(t)
					});
					var n = $r({}, e[e.length - 1].rect);
					if (t.thisAnimationDuration) {
						var r = vi(t, !0);
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
			e.splice(Ei(e, { target: t }), 1);
		},
		animateAll: function(n) {
			var r = this;
			if (!this.options.animation) {
				clearTimeout(t), typeof n == "function" && n();
				return;
			}
			var i = !1, a = 0;
			e.forEach(function(e) {
				var t = 0, n = e.target, o = n.fromRect, s = H(n), c = n.prevFromRect, l = n.prevToRect, u = e.rect, d = vi(n, !0);
				d && (s.top -= d.f, s.left -= d.e), n.toRect = s, n.thisAnimationDuration && ki(c, s) && !ki(o, s) && (u.top - s.top) / (u.left - s.left) === (o.top - s.top) / (o.left - s.left) && (t = Ri(u, c, l, r.options)), ki(s, o) || (n.prevFromRect = o, n.prevToRect = s, t ||= r.options.animation, r.animate(n, u, s, t)), t && (i = !0, a = Math.max(a, t), clearTimeout(n.animationResetTimer), n.animationResetTimer = setTimeout(function() {
					n.animationTime = 0, n.prevFromRect = null, n.fromRect = null, n.prevToRect = null, n.thisAnimationDuration = null;
				}, t), n.thisAnimationDuration = t);
			}), clearTimeout(t), i ? t = setTimeout(function() {
				typeof n == "function" && n();
			}, a) : typeof n == "function" && n(), e = [];
		},
		animate: function(e, t, n, r) {
			if (r) {
				V(e, "transition", ""), V(e, "transform", "");
				var i = vi(this.el), a = i && i.a, o = i && i.d, s = (t.left - n.left) / (a || 1), c = (t.top - n.top) / (o || 1);
				e.animatingX = !!s, e.animatingY = !!c, V(e, "transform", "translate3d(" + s + "px," + c + "px,0)"), this.forRepaintDummy = Li(e), V(e, "transition", "transform " + r + "ms" + (this.options.easing ? " " + this.options.easing : "")), V(e, "transform", "translate3d(0,0,0)"), typeof e.animated == "number" && clearTimeout(e.animated), e.animated = setTimeout(function() {
					V(e, "transition", ""), V(e, "transform", ""), e.animated = !1, e.animatingX = !1, e.animatingY = !1;
				}, r);
			}
		}
	};
}
function Li(e) {
	return e.offsetWidth;
}
function Ri(e, t, n, r) {
	return Math.sqrt((t.top - e.top) ** 2 + (t.left - e.left) ** 2) / Math.sqrt((t.top - n.top) ** 2 + (t.left - n.left) ** 2) * r.animation;
}
var zi = [], Bi = { initializeByDefault: !0 }, Vi = {
	mount: function(e) {
		for (var t in Bi) Bi.hasOwnProperty(t) && !(t in e) && (e[t] = Bi[t]);
		zi.forEach(function(t) {
			if (t.pluginName === e.pluginName) throw `Sortable: Cannot mount plugin ${e.pluginName} more than once`;
		}), zi.push(e);
	},
	pluginEvent: function(e, t, n) {
		var r = this;
		this.eventCanceled = !1, n.cancel = function() {
			r.eventCanceled = !0;
		};
		var i = e + "Global";
		zi.forEach(function(r) {
			t[r.pluginName] && (t[r.pluginName][i] && t[r.pluginName][i]($r({ sortable: t }, n)), t.options[r.pluginName] && t[r.pluginName][e] && t[r.pluginName][e]($r({ sortable: t }, n)));
		});
	},
	initializePlugins: function(e, t, n, r) {
		for (var i in zi.forEach(function(r) {
			var i = r.pluginName;
			if (!(!e.options[i] && !r.initializeByDefault)) {
				var a = new r(e, t, e.options);
				a.sortable = e, a.options = e.options, e[i] = a, ni(n, a.defaults);
			}
		}), e.options) if (e.options.hasOwnProperty(i)) {
			var a = this.modifyOption(e, i, e.options[i]);
			a !== void 0 && (e.options[i] = a);
		}
	},
	getEventProperties: function(e, t) {
		var n = {};
		return zi.forEach(function(r) {
			typeof r.eventProperties == "function" && ni(n, r.eventProperties.call(t[r.pluginName], e));
		}), n;
	},
	modifyOption: function(e, t, n) {
		var r;
		return zi.forEach(function(i) {
			e[i.pluginName] && i.optionListeners && typeof i.optionListeners[t] == "function" && (r = i.optionListeners[t].call(e[i.pluginName], n));
		}), r;
	}
};
function Hi(e) {
	var t = e.sortable, n = e.rootEl, r = e.name, i = e.targetEl, a = e.cloneEl, o = e.toEl, s = e.fromEl, c = e.oldIndex, l = e.newIndex, u = e.oldDraggableIndex, d = e.newDraggableIndex, f = e.originalEvent, p = e.putSortable, m = e.extraEventProperties;
	if (t ||= n && n[Fi], t) {
		var h, g = t.options, _ = "on" + r.charAt(0).toUpperCase() + r.substr(1);
		window.CustomEvent && !si && !ci ? h = new CustomEvent(r, {
			bubbles: !0,
			cancelable: !0
		}) : (h = document.createEvent("Event"), h.initEvent(r, !0, !0)), h.to = o || n, h.from = s || n, h.item = i || n, h.clone = a, h.oldIndex = c, h.newIndex = l, h.oldDraggableIndex = u, h.newDraggableIndex = d, h.originalEvent = f, h.pullMode = p ? p.lastPutMode : void 0;
		var v = $r($r({}, m), Vi.getEventProperties(r, t));
		for (var y in v) h[y] = v[y];
		n && n.dispatchEvent(h), g[_] && g[_].call(t, h);
	}
}
var Ui = ["evt"], U = function(e, t) {
	var n = arguments.length > 2 && arguments[2] !== void 0 ? arguments[2] : {}, r = n.evt, i = ii(n, Ui);
	Vi.pluginEvent.bind(Q)(e, t, $r({
		dragEl: G,
		parentEl: K,
		ghostEl: q,
		rootEl: J,
		nextEl: Wi,
		lastDownEl: Gi,
		cloneEl: Y,
		cloneHidden: Ki,
		dragStarted: sa,
		putSortable: X,
		activeSortable: Q.active,
		originalEvent: r,
		oldIndex: qi,
		oldDraggableIndex: Yi,
		newIndex: Ji,
		newDraggableIndex: Xi,
		hideGhostForTarget: Ta,
		unhideGhostForTarget: Ea,
		cloneNowHidden: function() {
			Ki = !0;
		},
		cloneNowShown: function() {
			Ki = !1;
		},
		dispatchSortableEvent: function(e) {
			W({
				sortable: t,
				name: e,
				originalEvent: r
			});
		}
	}, i));
};
function W(e) {
	Hi($r({
		putSortable: X,
		cloneEl: Y,
		targetEl: G,
		rootEl: J,
		oldIndex: qi,
		oldDraggableIndex: Yi,
		newIndex: Ji,
		newDraggableIndex: Xi
	}, e));
}
var G, K, q, J, Wi, Gi, Y, Ki, qi, Ji, Yi, Xi, Zi, X, Qi = !1, $i = !1, ea = [], ta, na, ra, ia, aa, oa, sa, ca, la, ua = !1, da = !1, fa, Z, pa = [], ma = !1, ha = [], ga = typeof document < "u", _a = di, va = ci || si ? "cssFloat" : "float", ya = ga && !fi && !di && "draggable" in document.createElement("div"), ba = function() {
	if (ga) {
		if (si) return !1;
		var e = document.createElement("x");
		return e.style.cssText = "pointer-events:auto", e.style.pointerEvents === "auto";
	}
}(), xa = function(e, t) {
	var n = V(e), r = parseInt(n.width) - parseInt(n.paddingLeft) - parseInt(n.paddingRight) - parseInt(n.borderLeftWidth) - parseInt(n.borderRightWidth), i = Si(e, 0, t), a = Si(e, 1, t), o = i && V(i), s = a && V(a), c = o && parseInt(o.marginLeft) + parseInt(o.marginRight) + H(i).width, l = s && parseInt(s.marginLeft) + parseInt(s.marginRight) + H(a).width;
	if (n.display === "flex") return n.flexDirection === "column" || n.flexDirection === "column-reverse" ? "vertical" : "horizontal";
	if (n.display === "grid") return n.gridTemplateColumns.split(" ").length <= 1 ? "vertical" : "horizontal";
	if (i && o.float && o.float !== "none") {
		var u = o.float === "left" ? "left" : "right";
		return a && (s.clear === "both" || s.clear === u) ? "vertical" : "horizontal";
	}
	return i && (o.display === "block" || o.display === "flex" || o.display === "table" || o.display === "grid" || c >= r && n[va] === "none" || a && n[va] === "none" && c + l > r) ? "vertical" : "horizontal";
}, Sa = function(e, t, n) {
	var r = n ? e.left : e.top, i = n ? e.right : e.bottom, a = n ? e.width : e.height, o = n ? t.left : t.top, s = n ? t.right : t.bottom, c = n ? t.width : t.height;
	return r === o || i === s || r + a / 2 === o + c / 2;
}, Ca = function(e, t) {
	var n;
	return ea.some(function(r) {
		var i = r[Fi].options.emptyInsertThreshold;
		if (!(!i || Ci(r))) {
			var a = H(r), o = e >= a.left - i && e <= a.right + i, s = t >= a.top - i && t <= a.bottom + i;
			if (o && s) return n = r;
		}
	}), n;
}, wa = function(e) {
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
	(!r || ei(r) != "object") && (r = { name: r }), n.name = r.name, n.checkPull = t(r.pull, !0), n.checkPut = t(r.put), n.revertClone = r.revertClone, e.group = n;
}, Ta = function() {
	!ba && q && V(q, "display", "none");
}, Ea = function() {
	!ba && q && V(q, "display", "");
};
ga && document.addEventListener("click", function(e) {
	if ($i) return e.preventDefault(), e.stopPropagation && e.stopPropagation(), e.stopImmediatePropagation && e.stopImmediatePropagation(), $i = !1, !1;
}, !0);
var Da = function(e) {
	if (G) {
		e = e.touches ? e.touches[0] : e;
		var t = Ca(e.clientX, e.clientY);
		if (t) {
			var n = {};
			for (var r in e) e.hasOwnProperty(r) && (n[r] = e[r]);
			n.target = n.rootEl = t, n.preventDefault = void 0, n.stopPropagation = void 0, t[Fi]._onDragOver(n);
		}
	}
}, Oa = function(e) {
	G && G.parentNode[Fi]._isOutsideThisEl(e.target);
};
function Q(e, t) {
	if (!(e && e.nodeType && e.nodeType === 1)) throw `Sortable: \`el\` must be an HTMLElement, not ${{}.toString.call(e)}`;
	this.el = e, this.options = t = ni({}, t), e[Fi] = this;
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
			return xa(e, this.options);
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
		supportPointer: Q.supportPointer !== !1 && "PointerEvent" in window && !ui,
		emptyInsertThreshold: 5
	};
	for (var r in Vi.initializePlugins(this, e, n), n) !(r in t) && (t[r] = n[r]);
	for (var i in wa(t), this) i.charAt(0) === "_" && typeof this[i] == "function" && (this[i] = this[i].bind(this));
	this.nativeDraggable = t.forceFallback ? !1 : ya, this.nativeDraggable && (this.options.touchStartThreshold = 1), t.supportPointer ? R(e, "pointerdown", this._onTapStart) : (R(e, "mousedown", this._onTapStart), R(e, "touchstart", this._onTapStart)), this.nativeDraggable && (R(e, "dragover", this), R(e, "dragenter", this)), ea.push(this.el), t.store && t.store.get && this.sort(t.store.get(this) || []), ni(this, Ii());
}
Q.prototype = {
	constructor: Q,
	_isOutsideThisEl: function(e) {
		!this.el.contains(e) && e !== this.el && (ca = null);
	},
	_getDirection: function(e, t) {
		return typeof this.options.direction == "function" ? this.options.direction.call(this, e, t, G) : this.options.direction;
	},
	_onTapStart: function(e) {
		if (e.cancelable) {
			var t = this, n = this.el, r = this.options, i = r.preventOnFilter, a = e.type, o = e.touches && e.touches[0] || e.pointerType && e.pointerType === "touch" && e, s = (o || e).target, c = e.target.shadowRoot && (e.path && e.path[0] || e.composedPath && e.composedPath()[0]) || s, l = r.filter;
			if (Ra(n), !G && !(/mousedown|pointerdown/.test(a) && e.button !== 0 || r.disabled) && !c.isContentEditable && !(!this.nativeDraggable && ui && s && s.tagName.toUpperCase() === "SELECT") && (s = gi(s, r.draggable, n, !1), !(s && s.animated) && Gi !== s)) {
				if (qi = wi(s), Yi = wi(s, r.draggable), typeof l == "function") {
					if (l.call(this, e, s, this)) {
						W({
							sortable: t,
							rootEl: c,
							name: "filter",
							targetEl: s,
							toEl: n,
							fromEl: n
						}), U("filter", t, { evt: e }), i && e.cancelable && e.preventDefault();
						return;
					}
				} else if (l && (l = l.split(",").some(function(r) {
					if (r = gi(c, r.trim(), n, !1), r) return W({
						sortable: t,
						rootEl: r,
						name: "filter",
						targetEl: s,
						fromEl: n,
						toEl: n
					}), U("filter", t, { evt: e }), !0;
				}), l)) {
					i && e.cancelable && e.preventDefault();
					return;
				}
				r.handle && !gi(c, r.handle, n, !1) || this._prepareDragStart(e, o, s);
			}
		}
	},
	_prepareDragStart: function(e, t, n) {
		var r = this, i = r.el, a = r.options, o = i.ownerDocument, s;
		if (n && !G && n.parentNode === i) {
			var c = H(n);
			if (J = i, G = n, K = G.parentNode, Wi = G.nextSibling, Gi = n, Zi = a.group, Q.dragged = G, ta = {
				target: G,
				clientX: (t || e).clientX,
				clientY: (t || e).clientY
			}, aa = ta.clientX - c.left, oa = ta.clientY - c.top, this._lastX = (t || e).clientX, this._lastY = (t || e).clientY, G.style["will-change"] = "all", s = function() {
				if (U("delayEnded", r, { evt: e }), Q.eventCanceled) {
					r._onDrop();
					return;
				}
				r._disableDelayedDragEvents(), !li && r.nativeDraggable && (G.draggable = !0), r._triggerDragStart(e, t), W({
					sortable: r,
					name: "choose",
					originalEvent: e
				}), B(G, a.chosenClass, !0);
			}, a.ignore.split(",").forEach(function(e) {
				yi(G, e.trim(), ja);
			}), R(o, "dragover", Da), R(o, "mousemove", Da), R(o, "touchmove", Da), R(o, "mouseup", r._onDrop), R(o, "touchend", r._onDrop), R(o, "touchcancel", r._onDrop), li && this.nativeDraggable && (this.options.touchStartThreshold = 4, G.draggable = !0), U("delayStart", this, { evt: e }), a.delay && (!a.delayOnTouchOnly || t) && (!this.nativeDraggable || !(ci || si))) {
				if (Q.eventCanceled) {
					this._onDrop();
					return;
				}
				R(o, "mouseup", r._disableDelayedDrag), R(o, "touchend", r._disableDelayedDrag), R(o, "touchcancel", r._disableDelayedDrag), R(o, "mousemove", r._delayedDragTouchMoveHandler), R(o, "touchmove", r._delayedDragTouchMoveHandler), a.supportPointer && R(o, "pointermove", r._delayedDragTouchMoveHandler), r._dragStartTimer = setTimeout(s, a.delay);
			} else s();
		}
	},
	_delayedDragTouchMoveHandler: function(e) {
		var t = e.touches ? e.touches[0] : e;
		Math.max(Math.abs(t.clientX - this._lastX), Math.abs(t.clientY - this._lastY)) >= Math.floor(this.options.touchStartThreshold / (this.nativeDraggable && window.devicePixelRatio || 1)) && this._disableDelayedDrag();
	},
	_disableDelayedDrag: function() {
		G && ja(G), clearTimeout(this._dragStartTimer), this._disableDelayedDragEvents();
	},
	_disableDelayedDragEvents: function() {
		var e = this.el.ownerDocument;
		z(e, "mouseup", this._disableDelayedDrag), z(e, "touchend", this._disableDelayedDrag), z(e, "touchcancel", this._disableDelayedDrag), z(e, "mousemove", this._delayedDragTouchMoveHandler), z(e, "touchmove", this._delayedDragTouchMoveHandler), z(e, "pointermove", this._delayedDragTouchMoveHandler);
	},
	_triggerDragStart: function(e, t) {
		t ||= e.pointerType == "touch" && e, !this.nativeDraggable || t ? this.options.supportPointer ? R(document, "pointermove", this._onTouchMove) : t ? R(document, "touchmove", this._onTouchMove) : R(document, "mousemove", this._onTouchMove) : (R(G, "dragend", this), R(J, "dragstart", this._onDragStart));
		try {
			document.selection ? za(function() {
				document.selection.empty();
			}) : window.getSelection().removeAllRanges();
		} catch {}
	},
	_dragStarted: function(e, t) {
		if (Qi = !1, J && G) {
			U("dragStarted", this, { evt: t }), this.nativeDraggable && R(document, "dragover", Oa);
			var n = this.options;
			!e && B(G, n.dragClass, !1), B(G, n.ghostClass, !0), Q.active = this, e && this._appendGhost(), W({
				sortable: this,
				name: "start",
				originalEvent: t
			});
		} else this._nulling();
	},
	_emulateDragOver: function() {
		if (na) {
			this._lastX = na.clientX, this._lastY = na.clientY, Ta();
			for (var e = document.elementFromPoint(na.clientX, na.clientY), t = e; e && e.shadowRoot && (e = e.shadowRoot.elementFromPoint(na.clientX, na.clientY), e !== t);) t = e;
			if (G.parentNode[Fi]._isOutsideThisEl(e), t) do {
				if (t[Fi]) {
					var n = void 0;
					if (n = t[Fi]._onDragOver({
						clientX: na.clientX,
						clientY: na.clientY,
						target: e,
						rootEl: t
					}), n && !this.options.dragoverBubble) break;
				}
				e = t;
			} while (t = t.parentNode);
			Ea();
		}
	},
	_onTouchMove: function(e) {
		if (ta) {
			var t = this.options, n = t.fallbackTolerance, r = t.fallbackOffset, i = e.touches ? e.touches[0] : e, a = q && vi(q, !0), o = q && a && a.a, s = q && a && a.d, c = _a && Z && Ti(Z), l = (i.clientX - ta.clientX + r.x) / (o || 1) + (c ? c[0] - pa[0] : 0) / (o || 1), u = (i.clientY - ta.clientY + r.y) / (s || 1) + (c ? c[1] - pa[1] : 0) / (s || 1);
			if (!Q.active && !Qi) {
				if (n && Math.max(Math.abs(i.clientX - this._lastX), Math.abs(i.clientY - this._lastY)) < n) return;
				this._onDragStart(e, !0);
			}
			if (q) {
				a ? (a.e += l - (ra || 0), a.f += u - (ia || 0)) : a = {
					a: 1,
					b: 0,
					c: 0,
					d: 1,
					e: l,
					f: u
				};
				var d = `matrix(${a.a},${a.b},${a.c},${a.d},${a.e},${a.f})`;
				V(q, "webkitTransform", d), V(q, "mozTransform", d), V(q, "msTransform", d), V(q, "transform", d), ra = l, ia = u, na = i;
			}
			e.cancelable && e.preventDefault();
		}
	},
	_appendGhost: function() {
		if (!q) {
			var e = this.options.fallbackOnBody ? document.body : J, t = H(G, !0, _a, !0, e), n = this.options;
			if (_a) {
				for (Z = e; V(Z, "position") === "static" && V(Z, "transform") === "none" && Z !== document;) Z = Z.parentNode;
				Z !== document.body && Z !== document.documentElement ? (Z === document && (Z = bi()), t.top += Z.scrollTop, t.left += Z.scrollLeft) : Z = bi(), pa = Ti(Z);
			}
			q = G.cloneNode(!0), B(q, n.ghostClass, !1), B(q, n.fallbackClass, !0), B(q, n.dragClass, !0), V(q, "transition", ""), V(q, "transform", ""), V(q, "box-sizing", "border-box"), V(q, "margin", 0), V(q, "top", t.top), V(q, "left", t.left), V(q, "width", t.width), V(q, "height", t.height), V(q, "opacity", "0.8"), V(q, "position", _a ? "absolute" : "fixed"), V(q, "zIndex", "100000"), V(q, "pointerEvents", "none"), Q.ghost = q, e.appendChild(q), V(q, "transform-origin", aa / parseInt(q.style.width) * 100 + "% " + oa / parseInt(q.style.height) * 100 + "%");
		}
	},
	_onDragStart: function(e, t) {
		var n = this, r = e.dataTransfer, i = n.options;
		if (U("dragStart", this, { evt: e }), Q.eventCanceled) {
			this._onDrop();
			return;
		}
		U("setupClone", this), Q.eventCanceled || (Y = Pi(G), Y.draggable = !1, Y.style["will-change"] = "", this._hideClone(), B(Y, this.options.chosenClass, !1), Q.clone = Y), n.cloneId = za(function() {
			U("clone", n), !Q.eventCanceled && (n.options.removeCloneOnHide || J.insertBefore(Y, G), n._hideClone(), W({
				sortable: n,
				name: "clone"
			}));
		}), !t && B(G, i.dragClass, !0), t ? ($i = !0, n._loopId = setInterval(n._emulateDragOver, 50)) : (z(document, "mouseup", n._onDrop), z(document, "touchend", n._onDrop), z(document, "touchcancel", n._onDrop), r && (r.effectAllowed = "move", i.setData && i.setData.call(n, r, G)), R(document, "drop", n), V(G, "transform", "translateZ(0)")), Qi = !0, n._dragStartId = za(n._dragStarted.bind(n, t, e)), R(document, "selectstart", n), sa = !0, ui && V(document.body, "user-select", "none");
	},
	_onDragOver: function(e) {
		var t = this.el, n = e.target, r, i, a, o = this.options, s = o.group, c = Q.active, l = Zi === s, u = o.sort, d = X || c, f, p = this, m = !1;
		if (ma) return;
		function h(o, s) {
			U(o, p, $r({
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
					return Aa(J, t, G, r, n, H(n), e, i);
				},
				changed: v
			}, s));
		}
		function g() {
			h("dragOverAnimationCapture"), p.captureAnimationState(), p !== d && d.captureAnimationState();
		}
		function _(r) {
			return h("dragOverCompleted", { insertion: r }), r && (l ? c._hideClone() : c._showClone(p), p !== d && (B(G, X ? X.options.ghostClass : c.options.ghostClass, !1), B(G, o.ghostClass, !0)), X !== p && p !== Q.active ? X = p : p === Q.active && X && (X = null), d === p && (p._ignoreWhileAnimating = n), p.animateAll(function() {
				h("dragOverAnimationComplete"), p._ignoreWhileAnimating = null;
			}), p !== d && (d.animateAll(), d._ignoreWhileAnimating = null)), (n === G && !G.animated || n === t && !n.animated) && (ca = null), !o.dragoverBubble && !e.rootEl && n !== document && (G.parentNode[Fi]._isOutsideThisEl(e.target), !r && Da(e)), !o.dragoverBubble && e.stopPropagation && e.stopPropagation(), m = !0;
		}
		function v() {
			Ji = wi(G), Xi = wi(G, o.draggable), W({
				sortable: p,
				name: "change",
				toEl: t,
				newIndex: Ji,
				newDraggableIndex: Xi,
				originalEvent: e
			});
		}
		if (e.preventDefault !== void 0 && e.cancelable && e.preventDefault(), n = gi(n, o.draggable, t, !0), h("dragOver"), Q.eventCanceled) return m;
		if (G.contains(e.target) || n.animated && n.animatingX && n.animatingY || p._ignoreWhileAnimating === n) return _(!1);
		if ($i = !1, c && !o.disabled && (l ? u || (a = K !== J) : X === this || (this.lastPutMode = Zi.checkPull(this, c, G, e)) && s.checkPut(this, c, G, e))) {
			if (f = this._getDirection(e, n) === "vertical", r = H(G), h("dragOverValid"), Q.eventCanceled) return m;
			if (a) return K = J, g(), this._hideClone(), h("revert"), Q.eventCanceled || (Wi ? J.insertBefore(G, Wi) : J.appendChild(G)), _(!0);
			var y = Ci(t, o.draggable);
			if (!y || Pa(e, f, this) && !y.animated) {
				if (y === G) return _(!1);
				if (y && t === e.target && (n = y), n && (i = H(n)), Aa(J, t, G, r, n, i, e, !!n) !== !1) return g(), t.appendChild(G), K = t, v(), _(!0);
			} else if (y && Na(e, f, this)) {
				var b = Si(t, 0, o, !0);
				if (b === G) return _(!1);
				if (n = b, i = H(n), Aa(J, t, G, r, n, i, e, !1) !== !1) return g(), t.insertBefore(G, b), K = t, v(), _(!0);
			} else if (n.parentNode === t) {
				i = H(n);
				var x = 0, S, C = G.parentNode !== t, w = !Sa(G.animated && G.toRect || r, n.animated && n.toRect || i, f), T = f ? "top" : "left", E = xi(n, "top", "top") || xi(G, "top", "top"), D = E ? E.scrollTop : void 0;
				ca !== n && (S = i[T], ua = !1, da = !w && o.invertSwap || C), x = Fa(e, n, i, f, w ? 1 : o.swapThreshold, o.invertedSwapThreshold == null ? o.swapThreshold : o.invertedSwapThreshold, da, ca === n);
				var O;
				if (x !== 0) {
					var k = wi(G);
					do
						k -= x, O = K.children[k];
					while (O && (V(O, "display") === "none" || O === q));
				}
				if (x === 0 || O === n) return _(!1);
				ca = n, la = x;
				var A = n.nextElementSibling, j = !1;
				j = x === 1;
				var M = Aa(J, t, G, r, n, i, e, j);
				if (M !== !1) return (M === 1 || M === -1) && (j = M === 1), ma = !0, setTimeout(Ma, 30), g(), j && !A ? t.appendChild(G) : n.parentNode.insertBefore(G, j ? A : n), E && Ni(E, 0, D - E.scrollTop), K = G.parentNode, S !== void 0 && !da && (fa = Math.abs(S - H(n)[T])), v(), _(!0);
			}
			if (t.contains(G)) return _(!1);
		}
		return !1;
	},
	_ignoreWhileAnimating: null,
	_offMoveEvents: function() {
		z(document, "mousemove", this._onTouchMove), z(document, "touchmove", this._onTouchMove), z(document, "pointermove", this._onTouchMove), z(document, "dragover", Da), z(document, "mousemove", Da), z(document, "touchmove", Da);
	},
	_offUpEvents: function() {
		var e = this.el.ownerDocument;
		z(e, "mouseup", this._onDrop), z(e, "touchend", this._onDrop), z(e, "pointerup", this._onDrop), z(e, "touchcancel", this._onDrop), z(document, "selectstart", this);
	},
	_onDrop: function(e) {
		var t = this.el, n = this.options;
		if (Ji = wi(G), Xi = wi(G, n.draggable), U("drop", this, { evt: e }), K = G && G.parentNode, Ji = wi(G), Xi = wi(G, n.draggable), Q.eventCanceled) {
			this._nulling();
			return;
		}
		Qi = !1, da = !1, ua = !1, clearInterval(this._loopId), clearTimeout(this._dragStartTimer), Ba(this.cloneId), Ba(this._dragStartId), this.nativeDraggable && (z(document, "drop", this), z(t, "dragstart", this._onDragStart)), this._offMoveEvents(), this._offUpEvents(), ui && V(document.body, "user-select", ""), V(G, "transform", ""), e && (sa && (e.cancelable && e.preventDefault(), !n.dropBubble && e.stopPropagation()), q && q.parentNode && q.parentNode.removeChild(q), (J === K || X && X.lastPutMode !== "clone") && Y && Y.parentNode && Y.parentNode.removeChild(Y), G && (this.nativeDraggable && z(G, "dragend", this), ja(G), G.style["will-change"] = "", sa && !Qi && B(G, X ? X.options.ghostClass : this.options.ghostClass, !1), B(G, this.options.chosenClass, !1), W({
			sortable: this,
			name: "unchoose",
			toEl: K,
			newIndex: null,
			newDraggableIndex: null,
			originalEvent: e
		}), J === K ? Ji !== qi && Ji >= 0 && (W({
			sortable: this,
			name: "update",
			toEl: K,
			originalEvent: e
		}), W({
			sortable: this,
			name: "sort",
			toEl: K,
			originalEvent: e
		})) : (Ji >= 0 && (W({
			rootEl: K,
			name: "add",
			toEl: K,
			fromEl: J,
			originalEvent: e
		}), W({
			sortable: this,
			name: "remove",
			toEl: K,
			originalEvent: e
		}), W({
			rootEl: K,
			name: "sort",
			toEl: K,
			fromEl: J,
			originalEvent: e
		}), W({
			sortable: this,
			name: "sort",
			toEl: K,
			originalEvent: e
		})), X && X.save()), Q.active && ((Ji == null || Ji === -1) && (Ji = qi, Xi = Yi), W({
			sortable: this,
			name: "end",
			toEl: K,
			originalEvent: e
		}), this.save()))), this._nulling();
	},
	_nulling: function() {
		U("nulling", this), J = G = K = q = Wi = Y = Gi = Ki = ta = na = sa = Ji = Xi = qi = Yi = ca = la = X = Zi = Q.dragged = Q.ghost = Q.clone = Q.active = null, ha.forEach(function(e) {
			e.checked = !0;
		}), ha.length = ra = ia = 0;
	},
	handleEvent: function(e) {
		switch (e.type) {
			case "drop":
			case "dragend":
				this._onDrop(e);
				break;
			case "dragenter":
			case "dragover":
				G && (this._onDragOver(e), ka(e));
				break;
			case "selectstart":
				e.preventDefault();
				break;
		}
	},
	toArray: function() {
		for (var e = [], t, n = this.el.children, r = 0, i = n.length, a = this.options; r < i; r++) t = n[r], gi(t, a.draggable, this.el, !1) && e.push(t.getAttribute(a.dataIdAttr) || La(t));
		return e;
	},
	sort: function(e, t) {
		var n = {}, r = this.el;
		this.toArray().forEach(function(e, t) {
			var i = r.children[t];
			gi(i, this.options.draggable, r, !1) && (n[e] = i);
		}, this), t && this.captureAnimationState(), e.forEach(function(e) {
			n[e] && (r.removeChild(n[e]), r.appendChild(n[e]));
		}), t && this.animateAll();
	},
	save: function() {
		var e = this.options.store;
		e && e.set && e.set(this);
	},
	closest: function(e, t) {
		return gi(e, t || this.options.draggable, this.el, !1);
	},
	option: function(e, t) {
		var n = this.options;
		if (t === void 0) return n[e];
		var r = Vi.modifyOption(this, e, t);
		r === void 0 ? n[e] = t : n[e] = r, e === "group" && wa(n);
	},
	destroy: function() {
		U("destroy", this);
		var e = this.el;
		e[Fi] = null, z(e, "mousedown", this._onTapStart), z(e, "touchstart", this._onTapStart), z(e, "pointerdown", this._onTapStart), this.nativeDraggable && (z(e, "dragover", this), z(e, "dragenter", this)), Array.prototype.forEach.call(e.querySelectorAll("[draggable]"), function(e) {
			e.removeAttribute("draggable");
		}), this._onDrop(), this._disableDelayedDragEvents(), ea.splice(ea.indexOf(this.el), 1), this.el = e = null;
	},
	_hideClone: function() {
		if (!Ki) {
			if (U("hideClone", this), Q.eventCanceled) return;
			V(Y, "display", "none"), this.options.removeCloneOnHide && Y.parentNode && Y.parentNode.removeChild(Y), Ki = !0;
		}
	},
	_showClone: function(e) {
		if (e.lastPutMode !== "clone") {
			this._hideClone();
			return;
		}
		if (Ki) {
			if (U("showClone", this), Q.eventCanceled) return;
			G.parentNode == J && !this.options.group.revertClone ? J.insertBefore(Y, G) : Wi ? J.insertBefore(Y, Wi) : J.appendChild(Y), this.options.group.revertClone && this.animate(G, Y), V(Y, "display", ""), Ki = !1;
		}
	}
};
function ka(e) {
	e.dataTransfer && (e.dataTransfer.dropEffect = "move"), e.cancelable && e.preventDefault();
}
function Aa(e, t, n, r, i, a, o, s) {
	var c, l = e[Fi], u = l.options.onMove, d;
	return window.CustomEvent && !si && !ci ? c = new CustomEvent("move", {
		bubbles: !0,
		cancelable: !0
	}) : (c = document.createEvent("Event"), c.initEvent("move", !0, !0)), c.to = t, c.from = e, c.dragged = n, c.draggedRect = r, c.related = i || t, c.relatedRect = a || H(t), c.willInsertAfter = s, c.originalEvent = o, e.dispatchEvent(c), u && (d = u.call(l, c, o)), d;
}
function ja(e) {
	e.draggable = !1;
}
function Ma() {
	ma = !1;
}
function Na(e, t, n) {
	var r = H(Si(n.el, 0, n.options, !0)), i = 10;
	return t ? e.clientX < r.left - i || e.clientY < r.top && e.clientX < r.right : e.clientY < r.top - i || e.clientY < r.bottom && e.clientX < r.left;
}
function Pa(e, t, n) {
	var r = H(Ci(n.el, n.options.draggable)), i = 10;
	return t ? e.clientX > r.right + i || e.clientX <= r.right && e.clientY > r.bottom && e.clientX >= r.left : e.clientX > r.right && e.clientY > r.top || e.clientX <= r.right && e.clientY > r.bottom + i;
}
function Fa(e, t, n, r, i, a, o, s) {
	var c = r ? e.clientY : e.clientX, l = r ? n.height : n.width, u = r ? n.top : n.left, d = r ? n.bottom : n.right, f = !1;
	if (!o) {
		if (s && fa < l * i) {
			if (!ua && (la === 1 ? c > u + l * a / 2 : c < d - l * a / 2) && (ua = !0), ua) f = !0;
			else if (la === 1 ? c < u + fa : c > d - fa) return -la;
		} else if (c > u + l * (1 - i) / 2 && c < d - l * (1 - i) / 2) return Ia(t);
	}
	return f ||= o, f && (c < u + l * a / 2 || c > d - l * a / 2) ? c > u + l / 2 ? 1 : -1 : 0;
}
function Ia(e) {
	return wi(G) < wi(e) ? 1 : -1;
}
function La(e) {
	for (var t = e.tagName + e.className + e.src + e.href + e.textContent, n = t.length, r = 0; n--;) r += t.charCodeAt(n);
	return r.toString(36);
}
function Ra(e) {
	ha.length = 0;
	for (var t = e.getElementsByTagName("input"), n = t.length; n--;) {
		var r = t[n];
		r.checked && ha.push(r);
	}
}
function za(e) {
	return setTimeout(e, 0);
}
function Ba(e) {
	return clearTimeout(e);
}
ga && R(document, "touchmove", function(e) {
	(Q.active || Qi) && e.cancelable && e.preventDefault();
}), Q.utils = {
	on: R,
	off: z,
	css: V,
	find: yi,
	is: function(e, t) {
		return !!gi(e, t, e, !1);
	},
	extend: Oi,
	throttle: ji,
	closest: gi,
	toggleClass: B,
	clone: Pi,
	index: wi,
	nextTick: za,
	cancelNextTick: Ba,
	detectDirection: xa,
	getChild: Si
}, Q.get = function(e) {
	return e[Fi];
}, Q.mount = function() {
	var e = [...arguments];
	e[0].constructor === Array && (e = e[0]), e.forEach(function(e) {
		if (!e.prototype || !e.prototype.constructor) throw `Sortable: Mounted plugin must be a constructor function, not ${{}.toString.call(e)}`;
		e.utils && (Q.utils = $r($r({}, Q.utils), e.utils)), Vi.mount(e);
	});
}, Q.create = function(e, t) {
	return new Q(e, t);
}, Q.version = ai;
var $ = [], Va, Ha, Ua = !1, Wa, Ga, Ka, qa;
function Ja() {
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
			this.sortable.nativeDraggable ? R(document, "dragover", this._handleAutoScroll) : this.options.supportPointer ? R(document, "pointermove", this._handleFallbackAutoScroll) : t.touches ? R(document, "touchmove", this._handleFallbackAutoScroll) : R(document, "mousemove", this._handleFallbackAutoScroll);
		},
		dragOverCompleted: function(e) {
			var t = e.originalEvent;
			!this.options.dragOverBubble && !t.rootEl && this._handleAutoScroll(t);
		},
		drop: function() {
			this.sortable.nativeDraggable ? z(document, "dragover", this._handleAutoScroll) : (z(document, "pointermove", this._handleFallbackAutoScroll), z(document, "touchmove", this._handleFallbackAutoScroll), z(document, "mousemove", this._handleFallbackAutoScroll)), Xa(), Ya(), Mi();
		},
		nulling: function() {
			Ka = Ha = Va = Ua = qa = Wa = Ga = null, $.length = 0;
		},
		_handleFallbackAutoScroll: function(e) {
			this._handleAutoScroll(e, !0);
		},
		_handleAutoScroll: function(e, t) {
			var n = this, r = (e.touches ? e.touches[0] : e).clientX, i = (e.touches ? e.touches[0] : e).clientY, a = document.elementFromPoint(r, i);
			if (Ka = e, t || this.options.forceAutoScrollFallback || ci || si || ui) {
				Za(e, this.options, a, t);
				var o = Di(a, !0);
				Ua && (!qa || r !== Wa || i !== Ga) && (qa && Xa(), qa = setInterval(function() {
					var a = Di(document.elementFromPoint(r, i), !0);
					a !== o && (o = a, Ya()), Za(e, n.options, a, t);
				}, 10), Wa = r, Ga = i);
			} else {
				if (!this.options.bubbleScroll || Di(a, !0) === bi()) {
					Ya();
					return;
				}
				Za(e, this.options, Di(a, !1), !1);
			}
		}
	}, ni(e, {
		pluginName: "scroll",
		initializeByDefault: !0
	});
}
function Ya() {
	$.forEach(function(e) {
		clearInterval(e.pid);
	}), $ = [];
}
function Xa() {
	clearInterval(qa);
}
var Za = ji(function(e, t, n, r) {
	if (t.scroll) {
		var i = (e.touches ? e.touches[0] : e).clientX, a = (e.touches ? e.touches[0] : e).clientY, o = t.scrollSensitivity, s = t.scrollSpeed, c = bi(), l = !1, u;
		Ha !== n && (Ha = n, Ya(), Va = t.scroll, u = t.scrollFn, Va === !0 && (Va = Di(n, !0)));
		var d = 0, f = Va;
		do {
			var p = f, m = H(p), h = m.top, g = m.bottom, _ = m.left, v = m.right, y = m.width, b = m.height, x = void 0, S = void 0, C = p.scrollWidth, w = p.scrollHeight, T = V(p), E = p.scrollLeft, D = p.scrollTop;
			p === c ? (x = y < C && (T.overflowX === "auto" || T.overflowX === "scroll" || T.overflowX === "visible"), S = b < w && (T.overflowY === "auto" || T.overflowY === "scroll" || T.overflowY === "visible")) : (x = y < C && (T.overflowX === "auto" || T.overflowX === "scroll"), S = b < w && (T.overflowY === "auto" || T.overflowY === "scroll"));
			var O = x && (Math.abs(v - i) <= o && E + y < C) - (Math.abs(_ - i) <= o && !!E), k = S && (Math.abs(g - a) <= o && D + b < w) - (Math.abs(h - a) <= o && !!D);
			if (!$[d]) for (var A = 0; A <= d; A++) $[A] || ($[A] = {});
			($[d].vx != O || $[d].vy != k || $[d].el !== p) && ($[d].el = p, $[d].vx = O, $[d].vy = k, clearInterval($[d].pid), (O != 0 || k != 0) && (l = !0, $[d].pid = setInterval(function() {
				r && this.layer === 0 && Q.active._onTouchMove(Ka);
				var t = $[this.layer].vy ? $[this.layer].vy * s : 0, n = $[this.layer].vx ? $[this.layer].vx * s : 0;
				typeof u == "function" && u.call(Q.dragged.parentNode[Fi], n, t, e, Ka, $[this.layer].el) !== "continue" || Ni($[this.layer].el, n, t);
			}.bind({ layer: d }), 24))), d++;
		} while (t.bubbleScroll && f !== c && (f = Di(f, !1)));
		Ua = l;
	}
}, 30), Qa = function(e) {
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
function $a() {}
$a.prototype = {
	startIndex: null,
	dragStart: function(e) {
		this.startIndex = e.oldDraggableIndex;
	},
	onSpill: function(e) {
		var t = e.dragEl, n = e.putSortable;
		this.sortable.captureAnimationState(), n && n.captureAnimationState();
		var r = Si(this.sortable.el, this.startIndex, this.options);
		r ? this.sortable.el.insertBefore(t, r) : this.sortable.el.appendChild(t), this.sortable.animateAll(), n && n.animateAll();
	},
	drop: Qa
}, ni($a, { pluginName: "revertOnSpill" });
function eo() {}
eo.prototype = {
	onSpill: function(e) {
		var t = e.dragEl, n = e.putSortable || this.sortable;
		n.captureAnimationState(), t.parentNode && t.parentNode.removeChild(t), n.animateAll();
	},
	drop: Qa
}, ni(eo, { pluginName: "removeOnSpill" }), Q.mount(new Ja()), Q.mount(eo, $a);
//#endregion
//#region resources/js/support/filters.ts
var to = {
	customer: "",
	status: "",
	search: "",
	unbilled: ""
};
function no(e) {
	return {
		customer: uo(e.customer),
		status: uo(e.status),
		search: lo(e.search).slice(0, 200),
		unbilled: e.unbilled === "1" ? "1" : ""
	};
}
function ro(e) {
	let t = {};
	for (let n of [
		"customer",
		"status",
		"search",
		"unbilled"
	]) e[n] !== "" && (t[n] = e[n]);
	return t;
}
function io(e) {
	return e.customer !== "" || e.status !== "" || e.search !== "" || e.unbilled !== "";
}
function ao(e) {
	return [
		e.customer,
		e.status,
		e.search,
		e.unbilled
	].join("|");
}
function oo(e, t) {
	return e.customer === t.customer && e.status === t.status && e.search === t.search && e.unbilled === t.unbilled;
}
function so(e) {
	let t = Number(e);
	return e !== "" && Number.isInteger(t) && t > 0 ? t : null;
}
function co(e) {
	let t = {}, n = so(e.customer);
	n !== null && (t.customer_id = n);
	let r = so(e.status);
	return r !== null && (t.status_id = r), e.search !== "" && (t.search = e.search), e.unbilled === "1" && (t.unbilled_only = !0), t;
}
function lo(e) {
	let t = Array.isArray(e) ? e[0] : e;
	return typeof t == "string" ? t.trim() : "";
}
function uo(e) {
	let t = lo(e);
	return so(t) === null ? "" : t;
}
//#endregion
//#region resources/js/components/TripCard.vue?vue&type=script&setup=true&lang.ts
var fo = { class: "flex items-start justify-between gap-2" }, po = { class: "min-w-0" }, mo = { class: "text-sm font-medium text-heading" }, ho = {
	key: 0,
	class: "inline-flex items-center rounded bg-primary-50 px-1.5 py-0.5 text-[11px] font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/20 ml-1"
}, go = {
	key: 1,
	class: "text-xs font-normal text-muted"
}, _o = { class: "relative" }, vo = {
	key: 0,
	class: "absolute end-0 z-20 mt-1 w-44 rounded-lg border border-line-default bg-surface p-1 shadow-lg"
}, yo = ["onClick"], bo = { class: "mt-1 text-sm text-muted" }, xo = {
	key: 0,
	class: "mt-1 text-xs text-muted"
}, So = { class: "mt-2 flex items-center justify-between gap-2" }, Co = { class: "text-xs text-muted" }, wo = { class: "mt-2.5 flex items-center justify-between border-t border-line-light pt-2 text-xs" }, To = { class: "flex items-center gap-1.5" }, Eo = {
	key: 0,
	class: "inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20"
}, Do = {
	key: 1,
	class: "inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20"
}, Oo = {
	key: 2,
	class: "inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-inset ring-slate-500/20"
}, ko = {
	key: 0,
	class: "text-[11px] text-muted"
}, Ao = { class: "font-medium text-heading" }, jo = /* @__PURE__ */ l({
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
		let c = n, l = o, u = v(!1), d = t(() => `${c.trip.from_city ?? "—"} → ${c.trip.to_city ?? "—"}`), p = t(() => {
			let e = c.trip.customers.map((e) => e.name).filter(Boolean);
			return e.length === 0 ? "No customer" : e.length === 1 ? e[0] : `${e[0]} +${e.length - 1} more`;
		}), m = t(() => c.trip.profit >= 0 ? "text-status-green" : "text-status-red");
		function h() {
			u.value = !u.value;
		}
		function g(e) {
			u.value = !1, l("move", c.trip, e);
		}
		function b() {
			u.value = !1, l("cancel", c.trip);
		}
		return (t, o) => (_(), i("article", {
			class: f(["cursor-pointer rounded-lg border border-line-default bg-surface p-3 shadow-sm hover:bg-hover", { "opacity-50": n.trip.cancelled_at }]),
			onClick: o[1] ||= (e) => l("open", n.trip)
		}, [
			a("div", fo, [a("div", po, [a("p", mo, [
				s(" #" + x(n.trip.trip_no) + " ", 1),
				n.trip.lr_numbers && n.trip.lr_numbers.length > 1 ? (_(), i("span", ho, " LR: " + x(n.trip.lr_numbers.join(", ")), 1)) : n.trip.lr_numbers && n.trip.lr_numbers.length === 1 && String(n.trip.trip_no) !== String(n.trip.lr_numbers[0]) ? (_(), i("span", go, " [LR: " + x(n.trip.lr_numbers[0]) + "] ", 1)) : r("", !0),
				s(" · " + x(d.value), 1)
			])]), a("div", _o, [a("button", {
				type: "button",
				class: "rounded px-1 text-muted hover:text-heading",
				"aria-label": "Trip menu",
				onClick: D(h, ["stop"])
			}, " ⋯ "), u.value ? (_(), i("div", vo, [
				a("button", {
					type: "button",
					class: "block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover",
					onClick: o[0] ||= (e) => {
						u.value = !1, l("open", n.trip);
					}
				}, " Open trip "),
				o[2] ||= a("p", { class: "px-2 pt-1.5 pb-0.5 text-xs font-medium text-muted" }, "Move to", -1),
				(_(!0), i(e, null, y(n.statuses.filter((e) => e.id !== n.trip.status_id), (e) => (_(), i("button", {
					key: e.id,
					type: "button",
					class: "block w-full rounded px-2 py-1.5 text-start text-sm text-body hover:bg-hover",
					onClick: (t) => g(e.id)
				}, x(e.name), 9, yo))), 128)),
				n.trip.cancelled_at ? r("", !0) : (_(), i("button", {
					key: 0,
					type: "button",
					class: "mt-1 block w-full rounded px-2 py-1.5 text-start text-sm text-status-red hover:bg-hover",
					onClick: b
				}, " Cancel trip "))
			])) : r("", !0)])]),
			a("p", bo, x(p.value), 1),
			n.trip.lorry_no ? (_(), i("p", xo, "🚛 " + x(n.trip.lorry_no), 1)) : r("", !0),
			a("div", So, [a("span", Co, x(n.trip.lr_count) + " LR" + x(n.trip.lr_count === 1 ? "" : "s") + " · " + x(S(L)(n.trip.revenue)), 1), a("span", { class: f(["text-sm font-semibold", m.value]) }, x(S(L)(n.trip.profit)), 3)]),
			a("div", wo, [a("div", To, [n.trip.payment_status === "PAID" ? (_(), i("span", Eo, " ● Payment Received ")) : n.trip.payment_status === "PARTIALLY_PAID" ? (_(), i("span", Do, " ◐ Partially Paid ")) : (_(), i("span", Oo, " ○ Payment Pending "))]), n.trip.customer_paid > 0 ? (_(), i("span", ko, [o[3] ||= s(" Recvd: ", -1), a("span", Ao, x(S(L)(n.trip.customer_paid)), 1)])) : r("", !0)])
		], 2));
	}
}), Mo = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, No = {
	key: 1,
	class: "rounded-2xl border border-line-default bg-surface p-12 text-center"
}, Po = {
	key: 2,
	class: "flex items-stretch gap-4 overflow-x-auto pb-4"
}, Fo = { class: "flex shrink-0 items-center gap-2 border-b border-line-default bg-surface-secondary/60 px-3 py-2.5" }, Io = { class: "truncate text-sm font-semibold text-heading" }, Lo = { class: "ml-auto shrink-0 rounded-full bg-surface-tertiary px-2 py-0.5 text-xs font-medium text-muted" }, Ro = ["data-status-id"], zo = ["data-trip-id"], Bo = {
	key: 0,
	class: "rounded-lg border border-dashed border-line-default py-10 text-center text-xs text-muted"
}, Vo = /* @__PURE__ */ l({
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
		let o = n, s = v([]), l = v(!0), u = /* @__PURE__ */ new Map(), d = /* @__PURE__ */ new Map(), f = t(() => o.statuses.map((e) => ({
			status: e,
			cards: s.value.filter((t) => t.status_id === e.id).sort((e, t) => e.board_position - t.board_position)
		}))), g = t(() => !l.value && s.value.length === 0);
		async function b() {
			l.value = !0;
			try {
				s.value = await je(o.client, co(o.filters));
			} catch (e) {
				o.notify("error", I(e, "Unable to load the board."));
			} finally {
				l.value = !1;
			}
		}
		h(() => {
			b();
		}), w(() => ao(o.filters), () => void b()), m(() => {
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
		function C(e) {
			let t = e.item, n = e.oldIndex ?? 0;
			t.parentNode?.removeChild(t), e.from.insertBefore(t, e.from.children[n] ?? null);
		}
		async function T(e) {
			let t = Number(e.item.dataset.tripId), n = Number(e.to.dataset.statusId);
			if (C(e), !t || Number.isNaN(n)) return;
			let r = s.value.filter((e) => e.status_id === n && e.id !== t).sort((e, t) => e.board_position - t.board_position), i = r[e.newIndex - 1], a = r[e.newIndex], c = i && a ? (i.board_position + a.board_position) / 2 : a ? a.board_position - .5 : i ? i.board_position + .5 : 1;
			try {
				let e = await Fe(o.client, t, n, c);
				s.value = s.value.map((n) => n.id === t ? e : n);
			} catch (e) {
				o.notify("error", I(e, "Unable to move the trip.")), await b();
			}
		}
		function E(e) {
			o.router?.push(j.trip(e.id));
		}
		async function D(e, t) {
			try {
				let n = await Fe(o.client, e.id, t, null);
				s.value = s.value.map((t) => t.id === e.id ? n : t);
			} catch (e) {
				o.notify("error", I(e, "Unable to move the trip."));
			}
		}
		async function O(e) {
			if (window.confirm("Cancel this trip? It stays on the board, greyed out, and can never be billed.")) try {
				await Ie(o.client, e.id), o.notify("success", "Trip cancelled."), await b();
			} catch (e) {
				o.notify("error", I(e, "Unable to cancel the trip."));
			}
		}
		return (t, o) => (_(), i("div", null, [l.value ? (_(), i("div", Mo, "Loading the board…")) : g.value ? (_(), i("div", No, [...o[0] ||= [
			a("p", { class: "text-4xl" }, "🚛", -1),
			a("h2", { class: "mt-3 text-lg font-semibold text-heading" }, "No trips yet", -1),
			a("p", { class: "mt-1 text-sm text-muted" }, " Your board is empty. Create your first trip by mapping an existing LR Receipt, or by filling a new one. ", -1)
		]])) : (_(), i("div", Po, [(_(!0), i(e, null, y(f.value, (t) => (_(), i("section", {
			key: t.status.id,
			class: "flex w-72 shrink-0 flex-col overflow-hidden rounded-xl border border-line-default bg-surface shadow-sm"
		}, [
			a("div", {
				class: "h-1 w-full shrink-0",
				style: p({ backgroundColor: t.status.colour ?? "#94a3b8" })
			}, null, 4),
			a("header", Fo, [
				a("span", {
					class: "inline-block h-2.5 w-2.5 shrink-0 rounded-full",
					style: p({ backgroundColor: t.status.colour ?? "#94a3b8" })
				}, null, 4),
				a("span", Io, x(t.status.name), 1),
				a("span", Lo, x(t.cards.length), 1)
			]),
			a("div", {
				ref_for: !0,
				ref: S(t.status.id),
				"data-status-id": t.status.id,
				class: "min-h-44 flex-1 space-y-2 bg-surface-tertiary/40 p-2"
			}, [(_(!0), i(e, null, y(t.cards, (e) => (_(), i("div", {
				key: e.id,
				"data-trip-id": e.id
			}, [c(jo, {
				trip: e,
				statuses: n.statuses,
				onOpen: E,
				onMove: D,
				onCancel: O
			}, null, 8, ["trip", "statuses"])], 8, zo))), 128)), t.cards.length === 0 ? (_(), i("p", Bo, " Drop trips here ")) : r("", !0)], 8, Ro)
		]))), 128))]))]));
	}
}), Ho = {
	key: 0,
	class: "flex items-center justify-between gap-3 px-4 py-3 border-t md:px-6 border-line-light"
}, Uo = { class: "text-sm text-muted tabular" }, Wo = { class: "md:hidden" }, Go = { class: "hidden md:inline" }, Ko = { class: "font-medium text-body" }, qo = { class: "font-medium text-body" }, Jo = { class: "font-medium text-body" }, Yo = ["aria-label"], Xo = ["disabled", "aria-label"], Zo = { class: "items-center hidden gap-1 md:flex" }, Qo = ["aria-current"], $o = {
	key: 1,
	class: "px-1 text-sm text-subtle",
	"aria-hidden": "true"
}, es = [
	"aria-current",
	"aria-label",
	"onClick"
], ts = {
	key: 2,
	class: "px-1 text-sm text-subtle",
	"aria-hidden": "true"
}, ns = ["aria-current", "aria-label"], rs = ["disabled", "aria-label"], is = "flex items-center justify-center w-10 h-10 md:w-8 md:h-8 rounded-lg border border-line-default text-body hover:bg-hover disabled:opacity-40 disabled:pointer-events-none", as = /* @__PURE__ */ l({
	__name: "TripTablePagination",
	props: { pagination: {} },
	emits: ["pageChange"],
	setup(n, { emit: o }) {
		let l = n, u = o, d = ee();
		function p(e, t) {
			let n = d(e);
			return n && n !== e ? n : t;
		}
		let m = t(() => l.pagination.totalPages === void 0 ? [] : k()), h = t(() => l.pagination.currentPage >= 4 || l.pagination.totalPages < 10), g = t(() => l.pagination.totalPages > 1 && (l.pagination.currentPage <= l.pagination.totalPages - 3 || l.pagination.totalPages < 10)), v = t(() => l.pagination.currentPage >= 4 && l.pagination.totalPages >= 10), S = t(() => l.pagination.currentPage <= l.pagination.totalPages - 3 && l.pagination.totalPages >= 10), C = t(() => l.pagination.totalPages === void 0 || l.pagination.count === 0 ? !1 : l.pagination.totalPages > 1);
		function w(e) {
			return ["min-w-8 h-8 px-2 rounded-lg text-sm font-medium tabular transition-colors", D(e) ? "bg-primary-50 text-primary-700 font-semibold" : "text-body hover:bg-hover"].join(" ");
		}
		let T = t(() => l.pagination.currentPage * l.pagination.limit - (l.pagination.limit - 1)), E = t(() => Math.min(l.pagination.currentPage * l.pagination.limit, l.pagination.totalCount));
		function D(e) {
			return (l.pagination.currentPage || 1) === e;
		}
		function O(e) {
			e === l.pagination.currentPage || e > l.pagination.totalPages || e < 1 || u("pageChange", e);
		}
		function k() {
			let e = [], t = 2, n = l.pagination.totalPages - 1;
			l.pagination.totalPages >= 10 && (t = Math.max(1, l.pagination.currentPage - 2), n = Math.min(l.pagination.currentPage + 2, l.pagination.totalPages));
			for (let r = t; r <= n; r++) e.push(r);
			return e;
		}
		return (t, o) => {
			let l = b("BaseIcon");
			return C.value ? (_(), i("div", Ho, [a("p", Uo, [n.pagination.limit && n.pagination.currentPage ? (_(), i(e, { key: 0 }, [a("span", Wo, x(n.pagination.currentPage) + " / " + x(n.pagination.totalPages), 1), a("span", Go, [
				s(x(p("general.pagination.showing", "Showing")) + " ", 1),
				a("span", Ko, x(T.value), 1),
				s(" " + x(p("general.pagination.to", "to")) + " ", 1),
				a("span", qo, x(E.value), 1),
				s(" " + x(p("general.pagination.of", "of")) + " ", 1),
				a("span", Jo, x(n.pagination.totalCount), 1),
				s(" " + x(p("general.pagination.results", "results")), 1)
			])], 64)) : r("", !0)]), a("nav", {
				class: "flex items-center gap-1",
				"aria-label": p("general.pagination.label", "Pagination")
			}, [
				a("button", {
					type: "button",
					class: f(is),
					disabled: n.pagination.currentPage === 1,
					"aria-label": p("general.pagination.previous", "Previous"),
					onClick: o[0] ||= (e) => O(n.pagination.currentPage - 1)
				}, [c(l, {
					name: "ChevronLeftIcon",
					class: "w-4 h-4"
				})], 8, Xo),
				a("div", Zo, [
					h.value ? (_(), i("button", {
						key: 0,
						type: "button",
						class: f(w(1)),
						"aria-current": D(1) ? "page" : void 0,
						"aria-label": "Page 1",
						onClick: o[1] ||= (e) => O(1)
					}, " 1 ", 10, Qo)) : r("", !0),
					v.value ? (_(), i("span", $o, "…")) : r("", !0),
					(_(!0), i(e, null, y(m.value, (e) => (_(), i("button", {
						key: e,
						type: "button",
						class: f(w(e)),
						"aria-current": D(e) ? "page" : void 0,
						"aria-label": `Page ${e}`,
						onClick: (t) => O(e)
					}, x(e), 11, es))), 128)),
					S.value ? (_(), i("span", ts, "…")) : r("", !0),
					g.value ? (_(), i("button", {
						key: 3,
						type: "button",
						class: f(w(n.pagination.totalPages)),
						"aria-current": D(n.pagination.totalPages) ? "page" : void 0,
						"aria-label": `Page ${n.pagination.totalPages}`,
						onClick: o[2] ||= (e) => O(n.pagination.totalPages)
					}, x(n.pagination.totalPages), 11, ns)) : r("", !0)
				]),
				a("button", {
					type: "button",
					class: f(is),
					disabled: n.pagination.currentPage === n.pagination.totalPages,
					"aria-label": p("general.pagination.next", "Next"),
					onClick: o[3] ||= (e) => O(n.pagination.currentPage + 1)
				}, [c(l, {
					name: "ChevronRightIcon",
					class: "w-4 h-4"
				})], 8, rs)
			], 8, Yo)])) : r("", !0);
		};
	}
}), os = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, ss = {
	key: 1,
	class: "rounded-2xl border border-line-default bg-surface p-12 text-center"
}, cs = {
	key: 2,
	class: "rounded-2xl border border-line-default bg-surface overflow-hidden"
}, ls = { class: "overflow-x-auto" }, us = { class: "w-full text-sm" }, ds = { class: "border-b border-line-default text-start text-xs uppercase text-muted" }, fs = ["onClick"], ps = { class: "p-3 font-medium text-heading" }, ms = { class: "flex items-center gap-1.5 flex-wrap" }, hs = {
	key: 0,
	class: "inline-flex items-center rounded-md bg-primary-50 px-1.5 py-0.5 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-700/20",
	title: "Consolidated Multi-LR Load"
}, gs = {
	key: 1,
	class: "text-xs font-normal text-muted"
}, _s = { class: "p-3 text-body" }, vs = { class: "p-3 text-body" }, ys = { class: "p-3 text-body" }, bs = { class: "p-3 text-body" }, xs = { class: "p-3 text-end text-body font-medium" }, Ss = { class: "p-3 text-end font-medium text-emerald-600" }, Cs = { class: "p-3 text-end font-medium text-indigo-600" }, ws = { class: "p-3 text-end text-body" }, Ts = /* @__PURE__ */ l({
	__name: "TripsListView",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {},
		filters: {},
		statuses: {},
		loading: { type: Boolean }
	},
	setup(o) {
		let s = o, l = v([]), u = v(!0), d = v(1), m = v(10), g = v("trip_no"), C = v("desc");
		function T(e) {
			g.value === e ? C.value = C.value === "asc" ? "desc" : "asc" : (g.value = e, C.value = [
				"revenue",
				"actual_revenue",
				"tds_amount",
				"cost",
				"profit",
				"trip_no"
			].includes(e) ? "desc" : "asc"), d.value = 1;
		}
		let E = t(() => !u.value && l.value.length === 0), D = t(() => {
			let e = [...l.value], t = g.value, n = C.value === "asc" ? 1 : -1;
			return e.sort((e, r) => {
				let i = 0;
				switch (t) {
					case "trip_no":
						i = e.trip_no - r.trip_no;
						break;
					case "route": {
						let t = `${e.from_city ?? ""} ${e.to_city ?? ""}`.trim().toLowerCase(), n = `${r.from_city ?? ""} ${r.to_city ?? ""}`.trim().toLowerCase();
						i = t.localeCompare(n);
						break;
					}
					case "customer": {
						let t = (e.customers[0]?.name ?? "").toLowerCase(), n = (r.customers[0]?.name ?? "").toLowerCase();
						i = t.localeCompare(n);
						break;
					}
					case "lorry": {
						let t = (e.lorry_no ?? "").toLowerCase(), n = (r.lorry_no ?? "").toLowerCase();
						i = t.localeCompare(n);
						break;
					}
					case "status":
						i = e.status_id - r.status_id;
						break;
					case "revenue":
						i = e.revenue - r.revenue;
						break;
					case "actual_revenue":
						i = e.actual_revenue - r.actual_revenue;
						break;
					case "tds_amount":
						i = e.tds_amount - r.tds_amount;
						break;
					case "cost":
						i = e.cost - r.cost;
						break;
					case "profit":
						i = e.profit - r.profit;
						break;
				}
				return i === 0 ? r.trip_no - e.trip_no : i * n;
			});
		}), O = t(() => D.value.length), k = t(() => Math.max(1, Math.ceil(O.value / m.value))), A = t(() => {
			let e = (d.value - 1) * m.value;
			return D.value.slice(e, e + m.value);
		}), M = t(() => ({
			currentPage: d.value,
			totalPages: k.value,
			totalCount: O.value,
			count: A.value.length,
			limit: m.value
		}));
		function N(e) {
			d.value = e, typeof window < "u" && window.scrollTo({
				top: 0,
				behavior: "smooth"
			});
		}
		let ee = (e) => s.statuses.find((t) => t.id === e)?.name ?? "—", te = (e) => s.statuses.find((t) => t.id === e)?.colour ?? "#6366f1";
		async function ne() {
			u.value = !0;
			try {
				l.value = await je(s.client, co(s.filters));
			} catch (e) {
				s.notify("error", I(e, "Unable to load trips."));
			} finally {
				u.value = !1;
			}
		}
		h(() => {
			ne();
		}), w(() => ao(s.filters), () => {
			d.value = 1, ne();
		}), w(k, (e) => {
			d.value > e && (d.value = e);
		});
		function P(e) {
			s.router?.push(j.trip(e.id));
		}
		return (t, o) => {
			let s = b("BaseIcon");
			return _(), i("div", null, [u.value ? (_(), i("div", os, "Loading trips…")) : E.value ? (_(), i("div", ss, [...o[10] ||= [
				a("p", { class: "text-4xl" }, "🚛", -1),
				a("h2", { class: "mt-3 text-lg font-semibold text-heading" }, "No trips found", -1),
				a("p", { class: "mt-1 text-sm text-muted" }, "Try adjusting your filters, or create a new trip.", -1)
			]])) : (_(), i("div", cs, [a("div", ls, [a("table", us, [a("thead", null, [a("tr", ds, [
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "trip_no" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[0] ||= (e) => T("trip_no")
				}, [o[11] ||= a("span", null, "#", -1), g.value === "trip_no" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "route" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[1] ||= (e) => T("route")
				}, [o[12] ||= a("span", null, "Route", -1), g.value === "route" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "customer" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[2] ||= (e) => T("customer")
				}, [o[13] ||= a("span", null, "Customer", -1), g.value === "customer" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "lorry" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[3] ||= (e) => T("lorry")
				}, [o[14] ||= a("span", null, "Lorry", -1), g.value === "lorry" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-start font-medium", { "text-heading font-semibold": g.value === "status" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none",
					onClick: o[4] ||= (e) => T("status")
				}, [o[15] ||= a("span", null, "Status", -1), g.value === "status" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "revenue" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[5] ||= (e) => T("revenue")
				}, [o[16] ||= a("span", null, "Billed Rev", -1), g.value === "revenue" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "actual_revenue" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[6] ||= (e) => T("actual_revenue")
				}, [o[17] ||= a("span", null, "Actual Rev (Bank)", -1), g.value === "actual_revenue" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "tds_amount" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[7] ||= (e) => T("tds_amount")
				}, [o[18] ||= a("span", null, "TDS", -1), g.value === "tds_amount" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "cost" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[8] ||= (e) => T("cost")
				}, [o[19] ||= a("span", null, "Cost", -1), g.value === "cost" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2),
				a("th", { class: f(["p-3 text-end font-medium", { "text-heading font-semibold": g.value === "profit" }]) }, [a("button", {
					type: "button",
					class: "inline-flex items-center justify-end gap-1 -mx-1 px-1 py-0.5 rounded-md font-medium hover:text-heading hover:bg-hover transition-colors select-none ms-auto",
					onClick: o[9] ||= (e) => T("profit")
				}, [o[20] ||= a("span", null, "Profit", -1), g.value === "profit" ? (_(), n(s, {
					key: 0,
					name: C.value === "asc" ? "ChevronUpIcon" : "ChevronDownIcon",
					class: "w-3.5 h-3.5 text-primary-600"
				}, null, 8, ["name"])) : r("", !0)])], 2)
			])]), a("tbody", null, [(_(!0), i(e, null, y(A.value, (e) => (_(), i("tr", {
				key: e.id,
				class: f(["cursor-pointer border-b border-line-light hover:bg-hover", { "opacity-50": e.cancelled_at }]),
				onClick: (t) => P(e)
			}, [
				a("td", ps, [a("div", ms, [a("span", null, "#" + x(e.trip_no), 1), e.lr_numbers && e.lr_numbers.length > 1 ? (_(), i("span", hs, " LR: " + x(e.lr_numbers.join(", ")), 1)) : e.lr_numbers && e.lr_numbers.length === 1 && String(e.trip_no) !== String(e.lr_numbers[0]) ? (_(), i("span", gs, " [LR: " + x(e.lr_numbers[0]) + "] ", 1)) : r("", !0)])]),
				a("td", _s, x(e.from_city ?? "—") + " → " + x(e.to_city ?? "—"), 1),
				a("td", vs, x(e.customers.map((e) => e.name).filter(Boolean).join(", ") || "—"), 1),
				a("td", ys, x(e.lorry_no ?? "—"), 1),
				a("td", bs, [a("span", {
					class: "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold",
					style: p({
						backgroundColor: te(e.status_id) + "20",
						color: te(e.status_id)
					})
				}, x(ee(e.status_id)), 5)]),
				a("td", xs, x(e.revenue > 0 ? S(L)(e.revenue) : "—"), 1),
				a("td", Ss, x(e.actual_revenue > 0 ? S(L)(e.actual_revenue) : e.revenue > 0 ? S(L)(0) : "—"), 1),
				a("td", Cs, x(e.tds_amount > 0 ? S(L)(e.tds_amount) : e.revenue > 0 || e.actual_revenue > 0 ? S(L)(0) : "—"), 1),
				a("td", ws, x(S(L)(e.cost)), 1),
				a("td", { class: f(["p-3 text-end font-semibold", e.profit >= 0 ? "text-status-green" : "text-status-red"]) }, x(S(L)(e.profit)), 3)
			], 10, fs))), 128))])])]), c(as, {
				pagination: M.value,
				onPageChange: N
			}, null, 8, ["pagination"])]))]);
		};
	}
}), Es = { class: "mb-4 flex items-center justify-between gap-3" }, Ds = { class: "flex items-center gap-2" }, Os = { class: "text-sm font-semibold text-heading" }, ks = { class: "text-sm text-muted" }, As = {
	key: 0,
	class: "py-16 text-center text-sm text-muted"
}, js = { class: "grid grid-cols-1 gap-3 md:grid-cols-7" }, Ms = { class: "mb-2 flex items-center justify-between" }, Ns = { class: "text-xs font-medium text-muted" }, Ps = {
	key: 0,
	class: "rounded-full bg-surface-tertiary px-2 py-0.5 text-xs text-muted"
}, Fs = { class: "flex-1 space-y-2" }, Is = ["onClick"], Ls = { class: "truncate text-xs font-medium text-heading" }, Rs = {
	key: 0,
	class: "font-normal text-primary-600"
}, zs = { class: "mt-0.5 text-xs text-muted" }, Bs = {
	key: 0,
	class: "mt-2 border-t border-line-light pt-1 text-xs text-muted"
}, Vs = {
	key: 0,
	class: "mt-4"
}, Hs = { class: "mb-2 text-xs font-semibold uppercase tracking-wide text-muted" }, Us = { class: "flex flex-wrap gap-2" }, Ws = ["onClick"], Gs = { class: "text-xs font-medium text-heading" }, Ks = { class: "text-xs text-muted" }, qs = /* @__PURE__ */ l({
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
		let o = n, l = v([]), u = v(!0), d = v(M(/* @__PURE__ */ new Date())), p = [
			"Sun",
			"Mon",
			"Tue",
			"Wed",
			"Thu",
			"Fri",
			"Sat"
		], m = t(() => p.map((e, t) => {
			let n = new Date(d.value);
			n.setDate(n.getDate() + t);
			let r = N(n), i = l.value.filter((e) => e.pickup_date === r);
			return {
				name: e,
				date: n,
				dateStr: r,
				isToday: r === N(/* @__PURE__ */ new Date()),
				trips: i,
				revenue: i.reduce((e, t) => e + t.revenue, 0)
			};
		})), g = t(() => l.value.filter((e) => !e.pickup_date)), C = t(() => {
			let e = d.value, t = new Date(e);
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
				l.value = await je(o.client, co(o.filters));
			} catch (e) {
				o.notify("error", I(e, "Unable to load trips."));
			} finally {
				u.value = !1;
			}
		}
		h(() => {
			E();
		}), w(() => ao(o.filters), () => void E());
		function D() {
			let e = new Date(d.value);
			e.setDate(e.getDate() - 7), d.value = e;
		}
		function O() {
			let e = new Date(d.value);
			e.setDate(e.getDate() + 7), d.value = e;
		}
		function k() {
			d.value = M(/* @__PURE__ */ new Date());
		}
		function A(e) {
			o.router?.push(j.trip(e.id));
		}
		function M(e) {
			let t = new Date(e);
			return t.setHours(0, 0, 0, 0), t.setDate(t.getDate() - t.getDay()), t;
		}
		function N(e) {
			return `${e.getFullYear()}-${String(e.getMonth() + 1).padStart(2, "0")}-${String(e.getDate()).padStart(2, "0")}`;
		}
		return (t, n) => {
			let o = b("BaseButton");
			return _(), i("div", null, [a("div", Es, [a("div", Ds, [
				c(o, {
					size: "sm",
					variant: "white",
					onClick: D
				}, {
					default: T(() => [...n[0] ||= [s("←", -1)]]),
					_: 1
				}),
				a("span", Os, x(C.value), 1),
				c(o, {
					size: "sm",
					variant: "white",
					onClick: O
				}, {
					default: T(() => [...n[1] ||= [s("→", -1)]]),
					_: 1
				}),
				c(o, {
					size: "sm",
					variant: "white",
					onClick: k
				}, {
					default: T(() => [...n[2] ||= [s("Today", -1)]]),
					_: 1
				})
			]), a("span", ks, x(l.value.length) + " trips this week", 1)]), u.value ? (_(), i("div", As, "Loading the week…")) : (_(), i(e, { key: 1 }, [a("div", js, [(_(!0), i(e, null, y(m.value, (t) => (_(), i("div", {
				key: t.dateStr,
				class: f(["flex min-h-48 flex-col rounded-xl border bg-surface p-2", t.isToday ? "border-primary-400" : "border-line-default"])
			}, [
				a("div", Ms, [a("div", null, [a("p", Ns, x(t.name), 1), a("p", { class: f(["text-lg font-semibold", t.isToday ? "text-primary-500" : "text-heading"]) }, x(t.date.getDate()), 3)]), t.trips.length > 0 ? (_(), i("span", Ps, x(t.trips.length), 1)) : r("", !0)]),
				a("div", Fs, [(_(!0), i(e, null, y(t.trips, (e) => (_(), i("div", {
					key: e.id,
					class: "cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover",
					onClick: (t) => A(e)
				}, [a("p", Ls, [
					s(" #" + x(e.trip_no) + " ", 1),
					e.lr_numbers && e.lr_numbers.length > 1 ? (_(), i("span", Rs, " [" + x(e.lr_numbers.join(", ")) + "] ", 1)) : r("", !0),
					s(" · " + x(e.from_city ?? "—") + " → " + x(e.to_city ?? "—"), 1)
				]), a("p", zs, x(e.lorry_no ?? "No lorry") + " · " + x(S(L)(e.revenue)), 1)], 8, Is))), 128))]),
				t.trips.length > 0 ? (_(), i("p", Bs, x(S(L)(t.revenue)), 1)) : r("", !0)
			], 2))), 128))]), g.value.length > 0 ? (_(), i("div", Vs, [a("p", Hs, " Unscheduled (" + x(g.value.length) + ") ", 1), a("div", Us, [(_(!0), i(e, null, y(g.value, (e) => (_(), i("div", {
				key: e.id,
				class: "cursor-pointer rounded-lg border border-line-light bg-surface p-2 hover:bg-hover",
				onClick: (t) => A(e)
			}, [a("p", Gs, "#" + x(e.trip_no), 1), a("p", Ks, x(e.from_city ?? "—") + " → " + x(e.to_city ?? "—"), 1)], 8, Ws))), 128))])])) : r("", !0)], 64))]);
		};
	}
}), Js = { class: "mt-4 flex flex-wrap items-end gap-3" }, Ys = { class: "min-w-44 flex-1" }, Xs = { class: "min-w-44 flex-1" }, Zs = { class: "min-w-44 flex-1" }, Qs = { class: "flex items-center gap-1.5 pb-2.5 text-sm text-body" }, $s = ["checked"], ec = 350, tc = /* @__PURE__ */ l({
	__name: "TripFilters",
	props: {
		modelValue: {},
		statuses: {}
	},
	emits: ["update:modelValue"],
	setup(e, { emit: n }) {
		let o = e, l = n, u = v(o.modelValue.search), d, f = t(() => [{
			id: "",
			label: "All statuses"
		}, ...o.statuses.map((e) => ({
			id: String(e.id),
			label: e.name
		}))]), p = t({
			get: () => g(f.value, o.modelValue.status),
			set: (e) => y({ status: e?.id ?? "" })
		}), h = t(() => io(o.modelValue));
		w(() => o.modelValue.search, (e) => {
			e !== u.value && (u.value = e);
		}), w(u, (e) => {
			clearTimeout(d), d = setTimeout(() => y({ search: e.trim() }), ec);
		}), m(() => clearTimeout(d));
		function g(e, t) {
			return e.find((e) => e.id === t) ?? e[0];
		}
		function y(e) {
			l("update:modelValue", {
				...o.modelValue,
				...e
			});
		}
		function x() {
			y({ unbilled: o.modelValue.unbilled === "1" ? "" : "1" });
		}
		function S() {
			u.value = "", l("update:modelValue", { ...to });
		}
		return (t, n) => {
			let o = b("BaseSelectInput"), l = b("BaseCustomerSelectInput"), d = b("BaseInput");
			return _(), i("div", Js, [
				a("label", Ys, [n[3] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Status", -1), c(o, {
					modelValue: p.value,
					"onUpdate:modelValue": n[0] ||= (e) => p.value = e,
					options: f.value,
					"label-key": "label"
				}, null, 8, ["modelValue", "options"])]),
				a("label", Xs, [n[4] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Customer", -1), c(l, {
					"model-value": e.modelValue.customer ? Number(e.modelValue.customer) : null,
					"can-deselect": "",
					placeholder: "All customers",
					"onUpdate:modelValue": n[1] ||= (e) => y({ customer: e ? String(e) : "" })
				}, null, 8, ["model-value"])]),
				a("label", Zs, [n[5] ||= a("span", { class: "mb-1 block text-xs font-medium text-muted" }, "Search", -1), c(d, {
					modelValue: u.value,
					"onUpdate:modelValue": n[2] ||= (e) => u.value = e,
					type: "text",
					name: "search",
					autocomplete: "off",
					placeholder: "Search route, lorry, goods..."
				}, null, 8, ["modelValue"])]),
				a("label", Qs, [a("input", {
					type: "checkbox",
					class: "accent-primary-600",
					checked: e.modelValue.unbilled === "1",
					onChange: x
				}, null, 40, $s), n[6] ||= s(" Unbilled only ", -1)]),
				h.value ? (_(), i("button", {
					key: 0,
					type: "button",
					class: "pb-2.5 text-sm font-medium text-primary-500 hover:underline",
					onClick: S
				}, " Clear ")) : r("", !0)
			]);
		};
	}
}), nc = {
	class: "inline-flex overflow-hidden rounded-lg border border-line-default",
	"aria-label": "Trip views"
}, rc = ["aria-current", "onClick"], ic = { class: "max-sm:sr-only" }, ac = /* @__PURE__ */ l({
	__name: "TripViewSwitcher",
	props: {
		active: {},
		query: {}
	},
	emits: ["select"],
	setup(t, { emit: n }) {
		let r = t, o = n, s = [
			{
				id: "board",
				name: M.board,
				label: "Board",
				icon: "ViewColumnsIcon"
			},
			{
				id: "list",
				name: M.list,
				label: "List",
				icon: "ListBulletIcon"
			},
			{
				id: "week",
				name: M.week,
				label: "Week",
				icon: "CalendarDaysIcon"
			}
		];
		function l(e) {
			return r.active === e.name || e.id === "board" && r.active === M.trips;
		}
		function u(e) {
			l(e) || o("select", {
				name: e.name,
				query: r.query
			});
		}
		return (t, n) => {
			let r = b("BaseIcon");
			return _(), i("nav", nc, [(_(), i(e, null, y(s, (e) => a("button", {
				key: e.id,
				type: "button",
				class: f(["flex items-center gap-1.5 border-e border-line-default px-3 py-1.5 text-sm font-medium last:border-e-0", l(e) ? "bg-primary-50 text-primary-500" : "bg-surface text-muted hover:bg-hover hover:text-heading"]),
				"aria-current": l(e) ? "page" : void 0,
				onClick: (t) => u(e)
			}, [c(r, {
				name: e.icon,
				class: "h-4 w-4"
			}, null, 8, ["name"]), a("span", ic, x(e.label), 1)], 10, rc)), 64))]);
		};
	}
}), oc = { class: "flex flex-wrap items-center justify-end gap-3" }, sc = /* @__PURE__ */ l({
	__name: "TripsPage",
	props: {
		client: { type: [Function, Object] },
		notify: { type: Function },
		router: {}
	},
	setup(e) {
		let r = e, i = r.router, o = v([]), l = v(!0), u = t(() => r.router.currentRoute.value), d = t(() => no(u.value.query)), p = t(() => ro(d.value)), m = t(() => String(u.value.name ?? ""));
		w(m, (e) => g(e)), h(() => {
			g(m.value), y();
		});
		function g(e) {
			e === M.trips && i.replace({
				name: M.board,
				query: p.value
			});
		}
		async function y() {
			l.value = !0;
			try {
				o.value = await Me(r.client);
			} catch (e) {
				r.notify("error", I(e, "Unable to load statuses."));
			} finally {
				l.value = !1;
			}
		}
		function x(e) {
			oo(e, d.value) || i.replace({
				name: m.value === M.trips ? M.board : m.value,
				query: ro(e)
			});
		}
		function C(e) {
			i.push(e);
		}
		function E() {
			i.push(j.new);
		}
		return (e, t) => {
			let r = b("BaseBreadcrumbItem"), i = b("BaseBreadcrumb"), u = b("BaseIcon"), h = b("BaseButton"), g = b("router-link"), v = b("BasePageHeader"), y = b("router-view"), w = b("BasePage");
			return _(), n(w, null, {
				default: T(() => [
					c(v, { title: "Trips" }, {
						actions: T(() => [a("div", oc, [
							c(ac, {
								active: m.value,
								query: p.value,
								onSelect: C
							}, null, 8, ["active", "query"]),
							c(g, { to: S(j).reports }, {
								default: T(() => [c(h, { variant: "white" }, {
									left: T((e) => [c(u, {
										name: "ChartBarIcon",
										class: f(e.class)
									}, null, 8, ["class"])]),
									default: T(() => [t[0] ||= s(" Reports ", -1)]),
									_: 1
								})]),
								_: 1
							}, 8, ["to"]),
							c(h, {
								variant: "primary",
								onClick: E
							}, {
								left: T((e) => [c(u, {
									name: "PlusIcon",
									class: f(e.class)
								}, null, 8, ["class"])]),
								default: T(() => [t[1] ||= s(" New Trip ", -1)]),
								_: 1
							})
						])]),
						default: T(() => [c(i, null, {
							default: T(() => [c(r, {
								title: "Dashboard",
								to: "/admin/dashboard"
							}), c(r, {
								title: "Trips",
								to: "#",
								active: ""
							})]),
							_: 1
						})]),
						_: 1
					}),
					c(tc, {
						"model-value": d.value,
						statuses: o.value,
						"onUpdate:modelValue": x
					}, null, 8, ["model-value", "statuses"]),
					c(y, {
						filters: d.value,
						statuses: o.value,
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
}), cc = `${k}:view-trip`;
function lc(e) {
	e.registerPage({
		id: "trips",
		module: k,
		path: "",
		component: N(e, sc),
		meta: {
			ability: cc,
			title: "trips.title"
		},
		children: [
			{
				id: "board",
				module: k,
				path: "",
				component: N(e, Vo),
				meta: {
					ability: cc,
					title: "trips.title"
				}
			},
			{
				id: "list",
				module: k,
				path: "list",
				component: N(e, Ts),
				meta: {
					ability: cc,
					title: "trips.title"
				}
			},
			{
				id: "week",
				module: k,
				path: "week",
				component: N(e, qs),
				meta: {
					ability: cc,
					title: "trips.title"
				}
			}
		]
	}), e.registerPage({
		id: "new",
		module: k,
		path: "new",
		component: N(e, at),
		meta: {
			ability: cc,
			title: "trips.title"
		}
	}), e.registerPage({
		id: "trip",
		module: k,
		path: ":id(\\d+)",
		component: N(e, Zr),
		meta: {
			ability: cc,
			title: "trips.title"
		}
	});
}
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	n.addMessages(O), lc(n), Ae(n);
});
//#endregion
