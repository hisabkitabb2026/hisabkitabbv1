const { Fragment: e, Teleport: t, Transition: n, computed: r, createBlock: i, createCommentVNode: a, createElementBlock: o, createElementVNode: s, createTextVNode: c, createVNode: l, defineComponent: u, nextTick: d, normalizeStyle: f, onBeforeUnmount: p, onMounted: m, openBlock: h, reactive: g, ref: _, renderList: v, resolveComponent: y, shallowRef: b, toDisplayString: x, vModelText: S, watch: ee, withCtx: C, withDirectives: w, withModifiers: te } = window.__invoiceshelf_vue;
//#region resources/js/components/LorryPartySelectPopup.vue?vue&type=script&setup=true&lang.ts
var T = ["aria-label"], E = { class: "flex items-start gap-3" }, D = {
	class: "flex items-center justify-center w-11 h-11 text-sm font-semibold rounded-xl shrink-0 bg-btn-primary text-on-primary",
	"aria-hidden": "true"
}, O = { class: "flex-1 min-w-0" }, ne = { class: "text-xs font-medium text-muted" }, k = { class: "text-base font-semibold truncate text-heading" }, re = {
	key: 0,
	class: "text-sm truncate text-muted"
}, ie = { class: "flex items-center gap-1 -me-1 shrink-0" }, ae = ["aria-label"], oe = ["aria-label"], se = { key: 1 }, ce = ["aria-expanded"], le = { class: "flex flex-col flex-1 min-w-0" }, ue = { class: "text-base font-semibold text-heading" }, de = ["aria-label"], fe = { class: "relative" }, pe = { class: "flex flex-col overflow-y-auto border-t border-line-light max-h-80 overscroll-contain" }, me = ["onClick"], he = {
	class: "flex items-center justify-center w-10 h-10 text-sm font-semibold rounded-xl shrink-0 bg-primary-50 text-primary-700",
	"aria-hidden": "true"
}, ge = { class: "flex flex-col min-w-0" }, _e = { class: "flex items-center gap-2" }, ve = { class: "text-sm font-medium truncate text-heading" }, ye = { class: "text-xs px-2 py-0.5 rounded-full bg-primary-100 text-primary-700 font-medium" }, be = {
	key: 0,
	class: "text-sm truncate text-muted"
}, xe = ["title", "onClick"], Se = {
	class: "flex items-center justify-center w-10 h-10 text-sm font-semibold rounded-xl shrink-0 bg-surface-secondary text-muted",
	"aria-hidden": "true"
}, Ce = { class: "flex flex-col min-w-0" }, we = { class: "flex items-center gap-2" }, Te = { class: "text-sm font-medium truncate text-heading" }, Ee = {
	key: 0,
	class: "text-sm truncate text-muted"
}, De = {
	key: 0,
	class: "px-4 py-8 text-sm text-center text-muted",
	role: "status"
}, Oe = {
	key: 1,
	class: "flex flex-col gap-1 px-4 py-8 text-sm text-center",
	role: "status"
}, ke = {
	key: 0,
	class: "text-muted"
}, Ae = { class: "font-medium text-heading" }, je = { class: "w-full max-w-md p-6 bg-surface rounded-xl" }, Me = { class: "mb-4 text-lg font-semibold text-heading" }, Ne = { class: "space-y-3" }, Pe = { class: "mt-3 text-xs text-muted" }, Fe = { class: "flex gap-2 mt-4" }, Ie = { class: "w-full max-w-md p-6 bg-surface rounded-xl max-h-[90vh] overflow-y-auto" }, Le = { class: "mb-4 text-lg font-semibold text-heading" }, Re = { class: "space-y-3" }, ze = { class: "flex gap-2 mt-4" }, A = /* @__PURE__ */ u({
	__name: "LorryPartySelectPopup",
	props: {
		client: { type: [Function, Object] },
		type: {},
		label: {},
		modelValue: {}
	},
	emits: ["select", "clear"],
	setup(u, { emit: y }) {
		let b = u, A = y, j = _(!1), M = _(null), N = _([]), P = _([]), F = _(!1), I = _(b.modelValue ?? null), L = _(!1), R = _({
			name: "",
			phone: "",
			email: "",
			address: "",
			bank_account_no: "",
			licence_no: "",
			pan_number: ""
		}), z = _(null), B = _(null), V = _(null), H = _(null), U = g({
			top: "0px",
			left: "0px",
			width: "auto"
		});
		function W(e) {
			return e.split(" ").map((e) => e.charAt(0)).slice(0, 2).join("").toUpperCase();
		}
		async function G() {
			F.value = !0;
			try {
				let [e, t] = await Promise.all([b.client.get("/api/v1/lorry-receipts/lorry-party-profiles", { params: {
					type: b.type,
					search: M.value,
					limit: "all"
				} }), b.client.get("/api/v1/suppliers", { params: { limit: 100 } }).catch(() => null)]);
				N.value = e.data.data || [], P.value = t?.data?.data || [];
			} catch {
				N.value = [], P.value = [];
			} finally {
				F.value = !1;
			}
		}
		let K = r(() => {
			if (!M.value) return [];
			let e = new Set(N.value.map((e) => e.supplier_id).filter(Boolean)), t = M.value.toLowerCase();
			return P.value.filter((n) => !e.has(n.id) && (n.name.toLowerCase().includes(t) || (n.phone || "").includes(t)));
		});
		async function Be(e) {
			try {
				let { data: t } = await b.client.post("/api/v1/lorry-receipts/lorry-party-profiles", {
					type: b.type,
					supplier_id: e.id,
					name: e.name,
					phone: e.phone ?? ""
				});
				t?.data && X(t.data);
			} catch (e) {
				console.error("Failed to create profile from supplier", e);
			}
		}
		let q;
		function Ve() {
			clearTimeout(q), q = setTimeout(() => G(), 400);
		}
		function J() {
			if (!z.value) return;
			let e = z.value.getBoundingClientRect();
			U.top = e.bottom + window.scrollY + 8 + "px", U.left = e.left + window.scrollX + "px", U.width = e.width + "px";
		}
		function He() {
			j.value = !0, G(), d(() => {
				J(), H.value?.querySelector("input")?.focus();
			});
		}
		async function Y() {
			let e = j.value;
			j.value = !1, e && (await d(), (V.value ?? z.value)?.focus());
		}
		function X(e) {
			I.value = e, j.value = !1, M.value = null, A("select", e);
		}
		function Ue() {
			I.value = null, A("clear");
		}
		function We() {
			j.value = !1, R.value = {
				name: "",
				phone: "",
				email: "",
				address: "",
				bank_account_no: "",
				licence_no: "",
				pan_number: ""
			}, L.value = !0;
		}
		async function Ge() {
			try {
				let e = {
					type: b.type,
					name: R.value.name,
					phone: R.value.phone,
					email: R.value.email || null,
					address: R.value.address,
					bank_account_no: R.value.bank_account_no
				};
				b.type === "DRIVER" && (e.licence_no = R.value.licence_no), (b.type === "OWNER" || b.type === "BROKER") && (e.pan_number = R.value.pan_number);
				let { data: t } = await b.client.post("/api/v1/lorry-receipts/lorry-party-profiles", e);
				L.value = !1, t?.data && X(t.data);
			} catch (e) {
				console.error("Failed to create profile", e);
			}
		}
		let Ke = r(() => {
			let e = window;
			return typeof e.__lorryReceiptCan == "function" ? !!e.__lorryReceiptCan("lorry-receipt:manage-party-profiles") : !0;
		}), Z = _(!1), Q = _({
			name: "",
			phone: "",
			address: "",
			bank_account_no: "",
			pan_number: "",
			licence_no: "",
			licence_date: "",
			rto_address: "",
			valid_up_to: ""
		});
		function qe() {
			let e = I.value;
			e && (Q.value = {
				name: e.name ?? "",
				phone: e.phone ?? "",
				address: e.address ?? "",
				bank_account_no: e.bank_account_no ?? "",
				pan_number: e.pan_number ?? "",
				licence_no: e.licence_no ?? "",
				licence_date: e.licence_date ?? "",
				rto_address: e.rto_address ?? "",
				valid_up_to: e.valid_up_to ?? ""
			}, Z.value = !0);
		}
		async function Je() {
			let e = I.value;
			if (!(!e || !e.id)) try {
				let { data: t } = await b.client.put(`/api/v1/lorry-receipts/lorry-party-profiles/${e.id}`, {
					type: b.type,
					...Q.value
				});
				Z.value = !1, t?.data && (G(), X(t.data));
			} catch (e) {
				console.error("Failed to update profile", e);
			}
		}
		function Ye(e) {
			if (!j.value) return;
			let t = e.target;
			B.value?.contains(t) || z.value?.contains(t) || Y();
		}
		function Xe(e) {
			e.key === "Escape" && j.value && Y();
		}
		function $() {
			j.value && J();
		}
		return m(() => {
			document.addEventListener("click", Ye), document.addEventListener("keydown", Xe), window.addEventListener("scroll", $, !0), window.addEventListener("resize", $);
		}), p(() => {
			document.removeEventListener("click", Ye), document.removeEventListener("keydown", Xe), window.removeEventListener("scroll", $, !0), window.removeEventListener("resize", $);
		}), ee(() => b.modelValue, (e) => {
			I.value = e ?? null;
		}), (r, d) => (h(), o("div", null, [
			I.value ? (h(), o("div", {
				key: 0,
				ref_key: "card",
				ref: V,
				tabindex: "-1",
				"aria-label": u.label + ": " + I.value.name,
				class: "flex flex-col gap-4 p-4 border md:p-5 glass rounded-xl focus:outline-hidden focus-visible:ring-2 focus-visible:ring-focus"
			}, [s("div", E, [
				s("span", D, x(W(I.value.name)), 1),
				s("div", O, [
					s("p", ne, x(u.label), 1),
					s("p", k, x(I.value.name), 1),
					I.value.phone || I.value.address ? (h(), o("p", re, x(I.value.phone || I.value.address), 1)) : a("", !0)
				]),
				s("div", ie, [I.value.id && Ke.value ? (h(), o("button", {
					key: 0,
					type: "button",
					class: "flex items-center justify-center w-10 h-10 transition-colors rounded-lg md:w-9 md:h-9 text-muted hover:bg-hover-strong hover:text-heading",
					"aria-label": "Edit " + u.label,
					onClick: qe
				}, [...d[22] ||= [s("svg", {
					class: "w-5 h-5",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
				})], -1)]], 8, ae)) : a("", !0), s("button", {
					type: "button",
					class: "flex items-center justify-center w-10 h-10 transition-colors rounded-lg md:w-9 md:h-9 text-muted hover:bg-hover-strong hover:text-heading",
					"aria-label": "Change " + u.label,
					onClick: Ue
				}, [...d[23] ||= [s("svg", {
					class: "w-5 h-5",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M6 18L18 6M6 6l12 12"
				})], -1)]], 8, oe)])
			])], 8, T)) : (h(), o("div", se, [s("button", {
				ref_key: "trigger",
				ref: z,
				type: "button",
				"aria-expanded": j.value,
				"aria-haspopup": "dialog",
				class: "flex items-center w-full gap-4 p-4 text-start transition-colors border-2 border-dashed md:p-5 rounded-xl bg-surface/50 hover:border-primary-400 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-focus",
				onClick: d[0] ||= (e) => j.value ? Y() : He()
			}, [
				d[25] ||= s("span", {
					class: "flex items-center justify-center w-11 h-11 rounded-xl shrink-0 bg-primary-50 text-primary-600",
					"aria-hidden": "true"
				}, [s("svg", {
					class: "w-5 h-5",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
				})])], -1),
				s("span", le, [s("span", ue, x(u.label), 1), d[24] ||= s("span", { class: "text-sm text-muted" }, "Click to select", -1)]),
				d[26] ||= s("svg", {
					class: "w-5 h-5 text-subtle shrink-0",
					fill: "none",
					stroke: "currentColor",
					viewBox: "0 0 24 24"
				}, [s("path", {
					"stroke-linecap": "round",
					"stroke-linejoin": "round",
					"stroke-width": "2",
					d: "M19 9l-7 7-7-7"
				})], -1)
			], 8, ce)])),
			(h(), i(t, { to: "body" }, [l(n, {
				"enter-active-class": "transition duration-150 ease-out",
				"enter-from-class": "translate-y-1 opacity-0",
				"leave-active-class": "transition duration-100 ease-in",
				"leave-to-class": "translate-y-1 opacity-0"
			}, {
				default: C(() => [j.value ? (h(), o("div", {
					key: 0,
					ref_key: "panel",
					ref: B,
					role: "dialog",
					"aria-label": u.label,
					style: f({
						position: "absolute",
						top: U.top,
						left: U.left,
						width: U.width,
						zIndex: 9999
					}),
					class: "overflow-hidden border glass-strong rounded-xl shadow-xl"
				}, [
					s("div", {
						ref_key: "searchField",
						ref: H,
						class: "p-3"
					}, [s("div", fe, [d[27] ||= s("svg", {
						class: "absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-subtle",
						fill: "none",
						stroke: "currentColor",
						viewBox: "0 0 24 24"
					}, [s("path", {
						"stroke-linecap": "round",
						"stroke-linejoin": "round",
						"stroke-width": "2",
						d: "M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
					})], -1), w(s("input", {
						"onUpdate:modelValue": d[1] ||= (e) => M.value = e,
						type: "search",
						placeholder: "Search...",
						class: "w-full py-2 pl-9 pr-3 text-sm border border-line-default rounded-lg bg-surface text-heading placeholder:text-subtle focus:outline-none focus:ring-2 focus:ring-focus",
						onInput: Ve
					}, null, 544), [[S, M.value]])])], 512),
					s("ul", pe, [
						(h(!0), o(e, null, v(N.value, (e) => (h(), o("li", { key: "p" + e.id }, [s("button", {
							type: "button",
							class: "flex items-center w-full gap-3 px-4 py-3 text-start transition-colors hover:bg-hover-strong focus:outline-hidden focus-visible:bg-hover-strong",
							onClick: (t) => X(e)
						}, [s("span", he, x(W(e.name)), 1), s("span", ge, [s("span", _e, [s("span", ve, x(e.name), 1), s("span", ye, x(e.type), 1)]), e.phone || e.address ? (h(), o("span", be, x(e.phone || e.address), 1)) : a("", !0)])], 8, me)]))), 128)),
						(h(!0), o(e, null, v(K.value, (e) => (h(), o("li", { key: "s" + e.id }, [s("button", {
							type: "button",
							class: "flex items-center w-full gap-3 px-4 py-3 text-start transition-colors hover:bg-hover-strong focus:outline-hidden focus-visible:bg-hover-strong",
							title: "Use this supplier as the " + u.label,
							onClick: (t) => Be(e)
						}, [s("span", Se, x(W(e.name)), 1), s("span", Ce, [s("span", we, [s("span", Te, x(e.name), 1), d[28] ||= s("span", { class: "text-xs px-2 py-0.5 rounded-full bg-surface-secondary text-muted font-medium" }, " Supplier ", -1)]), e.phone || e.email ? (h(), o("span", Ee, x(e.phone || e.email), 1)) : a("", !0)])], 8, xe)]))), 128)),
						F.value ? (h(), o("li", De, " Loading... ")) : N.value.length === 0 && K.value.length === 0 ? (h(), o("li", Oe, [M.value ? (h(), o("span", ke, "No matches found")) : (h(), o(e, { key: 1 }, [s("span", Ae, "No " + x(u.label) + " profiles yet", 1), d[29] ||= s("span", { class: "text-muted" }, "Add one to get started", -1)], 64))])) : a("", !0)
					]),
					Ke.value ? (h(), o("button", {
						key: 0,
						type: "button",
						class: "flex items-center justify-center w-full gap-2 text-sm font-medium transition-colors border-t h-12 border-line-light text-primary-600 hover:bg-primary-50/60",
						onClick: We
					}, [d[30] ||= s("svg", {
						class: "w-5 h-5",
						fill: "none",
						stroke: "currentColor",
						viewBox: "0 0 24 24"
					}, [s("path", {
						"stroke-linecap": "round",
						"stroke-linejoin": "round",
						"stroke-width": "2",
						d: "M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
					})], -1), c(" Add New " + x(u.label), 1)])) : a("", !0)
				], 12, de)) : a("", !0)]),
				_: 1
			})])),
			(h(), i(t, { to: "body" }, [L.value ? (h(), o("div", {
				key: 0,
				class: "fixed inset-0 z-[10000] flex items-center justify-center bg-black/50",
				onClick: d[10] ||= te((e) => L.value = !1, ["self"])
			}, [s("div", je, [
				s("h3", Me, "New " + x(u.label), 1),
				s("div", Ne, [
					w(s("input", {
						"onUpdate:modelValue": d[2] ||= (e) => R.value.name = e,
						placeholder: "Name *",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, R.value.name]]),
					w(s("input", {
						"onUpdate:modelValue": d[3] ||= (e) => R.value.phone = e,
						placeholder: "Phone",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, R.value.phone]]),
					w(s("input", {
						"onUpdate:modelValue": d[4] ||= (e) => R.value.email = e,
						type: "email",
						placeholder: "Email",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, R.value.email]]),
					w(s("textarea", {
						"onUpdate:modelValue": d[5] ||= (e) => R.value.address = e,
						placeholder: "Address",
						rows: "2",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, R.value.address]]),
					w(s("input", {
						"onUpdate:modelValue": d[6] ||= (e) => R.value.bank_account_no = e,
						placeholder: "Bank Account No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, R.value.bank_account_no]]),
					u.type === "DRIVER" ? w((h(), o("input", {
						key: 0,
						"onUpdate:modelValue": d[7] ||= (e) => R.value.licence_no = e,
						placeholder: "Licence No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[S, R.value.licence_no]]) : a("", !0),
					u.type === "OWNER" || u.type === "BROKER" ? w((h(), o("input", {
						key: 1,
						"onUpdate:modelValue": d[8] ||= (e) => R.value.pan_number = e,
						placeholder: "PAN No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[S, R.value.pan_number]]) : a("", !0)
				]),
				s("p", Pe, " Also saved as a Supplier, so bills and payments can be tracked against this " + x(u.label) + ". ", 1),
				s("div", Fe, [s("button", {
					type: "button",
					class: "flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700",
					onClick: Ge
				}, "Save"), s("button", {
					type: "button",
					class: "px-4 py-2 text-sm border border-line-default rounded-lg text-heading",
					onClick: d[9] ||= (e) => L.value = !1
				}, "Cancel")])
			])])) : a("", !0)])),
			(h(), i(t, { to: "body" }, [Z.value ? (h(), o("div", {
				key: 0,
				class: "fixed inset-0 z-[10000] flex items-center justify-center bg-black/50",
				onClick: d[21] ||= te((e) => Z.value = !1, ["self"])
			}, [s("div", Ie, [
				s("h3", Le, "Edit " + x(u.label), 1),
				s("div", Re, [
					w(s("input", {
						"onUpdate:modelValue": d[11] ||= (e) => Q.value.name = e,
						placeholder: "Name *",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, Q.value.name]]),
					w(s("input", {
						"onUpdate:modelValue": d[12] ||= (e) => Q.value.phone = e,
						placeholder: "Phone",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, Q.value.phone]]),
					w(s("textarea", {
						"onUpdate:modelValue": d[13] ||= (e) => Q.value.address = e,
						placeholder: "Address",
						rows: "2",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, Q.value.address]]),
					w(s("input", {
						"onUpdate:modelValue": d[14] ||= (e) => Q.value.bank_account_no = e,
						placeholder: "Bank Account No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512), [[S, Q.value.bank_account_no]]),
					u.type === "OWNER" || u.type === "BROKER" ? w((h(), o("input", {
						key: 0,
						"onUpdate:modelValue": d[15] ||= (e) => Q.value.pan_number = e,
						placeholder: "PAN No",
						class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
					}, null, 512)), [[S, Q.value.pan_number]]) : a("", !0),
					u.type === "DRIVER" ? (h(), o(e, { key: 1 }, [
						w(s("input", {
							"onUpdate:modelValue": d[16] ||= (e) => Q.value.licence_no = e,
							placeholder: "Licence No",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[S, Q.value.licence_no]]),
						w(s("input", {
							"onUpdate:modelValue": d[17] ||= (e) => Q.value.licence_date = e,
							type: "date",
							placeholder: "Licence Date",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[S, Q.value.licence_date]]),
						w(s("input", {
							"onUpdate:modelValue": d[18] ||= (e) => Q.value.rto_address = e,
							placeholder: "RTO",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[S, Q.value.rto_address]]),
						w(s("input", {
							"onUpdate:modelValue": d[19] ||= (e) => Q.value.valid_up_to = e,
							type: "date",
							placeholder: "Valid Up To",
							class: "w-full px-3 py-2 text-sm border border-line-default rounded-lg bg-surface text-heading"
						}, null, 512), [[S, Q.value.valid_up_to]])
					], 64)) : a("", !0)
				]),
				s("div", ze, [s("button", {
					type: "button",
					class: "flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700",
					onClick: Je
				}, "Save"), s("button", {
					type: "button",
					class: "px-4 py-2 text-sm border border-line-default rounded-lg text-heading",
					onClick: d[20] ||= (e) => Z.value = !1
				}, "Cancel")])
			])])) : a("", !0)]))
		]));
	}
}), j = {
	key: 0,
	class: "space-y-6 mt-5"
}, M = { class: "grid gap-4 grid-cols-1 md:grid-cols-3" }, N = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, P = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, F = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, I = { id: "lorry-advance-bank-list" }, L = ["value"], R = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, z = { id: "lorry-final-bank-list" }, B = ["value"], V = { class: "relative flex items-center" }, H = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, U = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, W = { class: "grid gap-x-4 gap-y-5 grid-cols-1 md:grid-cols-2 xl:grid-cols-3" }, G = /* @__PURE__ */ u({
	__name: "LorryReceiptFormFields",
	props: {
		templateName: {},
		store: {}
	},
	setup(t) {
		let n = t, i = r(() => n.store?.newInvoice), c = r(() => n.templateName === "lorry_receipt"), u = b(null);
		function d() {
			if (u.value) return u.value;
			let e = window;
			if (e.__lorryReceiptClient) return u.value = e.__lorryReceiptClient, u.value;
			throw Error("API client not initialized for LorryReceipt module");
		}
		let f = ["UMB", "VAPI"], p = _([]);
		async function g() {
			try {
				let { data: e } = await d().get("/api/v1/payment-methods", { params: { limit: "all" } });
				p.value = e?.data ?? [];
			} catch {
				p.value = [];
			}
		}
		let x = _([]), S = [
			"State Bank of India (SBI)",
			"HDFC Bank",
			"ICICI Bank",
			"Punjab National Bank (PNB)",
			"Bank of Baroda (BOB)",
			"Axis Bank",
			"Kotak Mahindra Bank",
			"Canara Bank",
			"Union Bank of India",
			"Bank of India",
			"Central Bank of India",
			"Indian Bank",
			"IndusInd Bank",
			"Yes Bank",
			"IDBI Bank",
			"Federal Bank",
			"Cash / Self"
		], w = r(() => {
			let e = x.value.map((e) => e.name), t = [...S, ...e];
			return i.value?.tr_advance_bank && t.push(String(i.value.tr_advance_bank)), i.value?.tr_final_bank && t.push(String(i.value.tr_final_bank)), Array.from(new Set(t.map((e) => e.trim()).filter(Boolean)));
		});
		async function te() {
			try {
				let { data: e } = await d().get("/api/v1/lorry-receipts/banks");
				x.value = e?.data ?? [];
			} catch {
				x.value = [];
			}
		}
		let T = [
			["tr_owner_name", "name"],
			["tr_owner_address", "address"],
			["tr_owner_phone", "phone"],
			["tr_owner_bank_account_no", "bank_account_no"],
			["tr_owner_pan_no", "pan_number"]
		], E = [
			["tr_driver_name", "name"],
			["tr_driver_address", "address"],
			["tr_driver_licence_no", "licence_no"],
			["tr_driver_licence_date", "licence_date"],
			["tr_driver_rto_address", "rto_address"],
			["tr_driver_valid_up_to", "valid_up_to"],
			["tr_driver_bank_account_no", "bank_account_no"]
		], D = [
			["tr_broker_name", "name"],
			["tr_broker_address", "address"],
			["tr_broker_pan_no", "pan_number"],
			["tr_advice_date", "advice_date"],
			["tr_broker_phone", "phone"],
			["tr_broker_bank_account_no", "bank_account_no"]
		];
		function O(e, t) {
			i.value && e.forEach(([e, n]) => {
				i.value[e] = t?.[n] || "";
			});
		}
		function ne(e) {
			if (!i.value) return null;
			let t = i.value, n = {};
			for (let [r, i] of e) n[i] = t[r] ?? "";
			return n.name ? {
				id: 0,
				type: "",
				...n
			} : null;
		}
		function k(e, t) {
			let n = ne(e);
			if (!n) return null;
			let r = Number(t) || 0;
			return r ? {
				...n,
				id: r
			} : n;
		}
		let re = r(() => k(T, i.value?.tr_owner_profile_id)), ie = r(() => k(E, i.value?.tr_driver_profile_id)), ae = r(() => k(D, i.value?.tr_broker_profile_id));
		function oe(e) {
			i.value && (i.value.tr_owner_profile_id = e.id), O(T, e), i.value && (i.value.tr_paid_to = e.name, i.value.tr_final_paid_to = e.name);
		}
		function se() {
			i.value && (i.value.tr_owner_profile_id = null), O(T, null);
		}
		function ce(e) {
			i.value && (i.value.tr_driver_profile_id = e.id), O(E, e);
		}
		function le() {
			i.value && (i.value.tr_driver_profile_id = null), O(E, null);
		}
		function ue(e) {
			i.value && (i.value.tr_broker_profile_id = e.id), O(D, e);
		}
		function de() {
			i.value && (i.value.tr_broker_profile_id = null), O(D, null);
		}
		function fe(e) {
			let t = i.value?.items;
			if (!t || !Array.isArray(t)) return;
			let n = Math.round(e * 100);
			if (t.length > 0) {
				let e = t[0];
				e.name = e.name || "Lorry Hire", e.quantity = 1, e.price = n, e.total = n;
				return;
			}
			t.push({
				id: `tr-lorry-hire-${Date.now()}`,
				invoice_id: null,
				item_id: null,
				name: "Lorry Hire",
				description: "Lorry Hire Charges",
				quantity: 1,
				price: n,
				discount_type: "fixed",
				discount_val: 0,
				discount: 0,
				total: n,
				totalTax: 0,
				taxes: []
			});
		}
		function pe() {
			if (!i.value) return;
			let e = Number(i.value.tr_lorry_hire_amount) || 0, t = Number(i.value.tr_other_charges_amount) || 0, n = Number(i.value.tr_advance_amount) || 0, r = Number(i.value.tr_detention_amount) || 0, a = Number(i.value.tr_extra_hire_amount) || 0, o = Number(i.value.tr_final_other_amount) || 0, s = Number(i.value.tr_less_advance_other_branch_amount) || 0, c = Number(i.value.tr_less_deduction_claims_amount) || 0, l = e + t, u = Math.max(0, l - n);
			i.value.tr_balance_amount = String(u);
			let d = l + r + a + o - n - s - c;
			i.value.tr_net_amount_payable = String(d);
			let f = Math.round(l * 100);
			i.value.sub_total = f, i.value.total = f, fe(l);
		}
		return ee(() => [
			i.value?.tr_lorry_hire_amount,
			i.value?.tr_other_charges_amount,
			i.value?.tr_advance_amount,
			i.value?.tr_detention_amount,
			i.value?.tr_extra_hire_amount,
			i.value?.tr_final_other_amount,
			i.value?.tr_less_advance_other_branch_amount,
			i.value?.tr_less_deduction_claims_amount,
			i.value?.tr_final_amount_paid
		], () => pe()), m(() => {
			g(), te();
		}), (t, n) => {
			let r = y("BaseCard"), u = y("BaseInput"), m = y("BaseInputGroup"), g = y("BaseSelectInput"), _ = y("BaseTextarea");
			return c.value && i.value ? (h(), o("div", j, [
				l(r, { class: "mb-4" }, {
					header: C(() => [...n[54] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Select Party (Owner / Driver / Broker)", -1)]]),
					default: C(() => [n[55] ||= s("p", { class: "text-sm text-muted mb-4" }, "Select a party profile to auto-fill the Owner, Driver, and Broker details below.", -1), s("div", M, [
						l(A, {
							client: d(),
							type: "OWNER",
							label: "Owner",
							"model-value": re.value,
							onSelect: oe,
							onClear: se
						}, null, 8, ["client", "model-value"]),
						l(A, {
							client: d(),
							type: "DRIVER",
							label: "Driver",
							"model-value": ie.value,
							onSelect: ce,
							onClear: le
						}, null, 8, ["client", "model-value"]),
						l(A, {
							client: d(),
							type: "BROKER",
							label: "Broker",
							"model-value": ae.value,
							onSelect: ue,
							onClear: de
						}, null, 8, ["client", "model-value"])
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...n[56] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Trip Details", -1)]]),
					default: C(() => [s("div", N, [
						l(m, { label: "From" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_from_name,
								"onUpdate:modelValue": n[0] ||= (e) => i.value.tr_from_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "To" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_to_name,
								"onUpdate:modelValue": n[1] ||= (e) => i.value.tr_to_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "LR Number" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_received_no_bilties,
								"onUpdate:modelValue": n[2] ||= (e) => i.value.tr_received_no_bilties = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "No Of Packages" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_no_of_packages,
								"onUpdate:modelValue": n[3] ||= (e) => i.value.tr_no_of_packages = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Actual Weight" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_actual_weight,
								"onUpdate:modelValue": n[4] ||= (e) => i.value.tr_actual_weight = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Charge Weight" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_charged_weight,
								"onUpdate:modelValue": n[5] ||= (e) => i.value.tr_charged_weight = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...n[57] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Vehicle Details", -1)]]),
					default: C(() => [s("div", P, [
						l(m, { label: "Lorry No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_lorry_no,
								"onUpdate:modelValue": n[6] ||= (e) => i.value.tr_lorry_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Regd at" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_regd_at,
								"onUpdate:modelValue": n[7] ||= (e) => i.value.tr_regd_at = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Body Type" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_body_type,
								"onUpdate:modelValue": n[8] ||= (e) => i.value.tr_body_type = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Make" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_make,
								"onUpdate:modelValue": n[9] ||= (e) => i.value.tr_make = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Model" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_vehicle_model,
								"onUpdate:modelValue": n[10] ||= (e) => i.value.tr_vehicle_model = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Colour" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_colour,
								"onUpdate:modelValue": n[11] ||= (e) => i.value.tr_colour = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Chasis No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_chasis_no,
								"onUpdate:modelValue": n[12] ||= (e) => i.value.tr_chasis_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Engine No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_engine_no,
								"onUpdate:modelValue": n[13] ||= (e) => i.value.tr_engine_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...n[58] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Hire Particulars", -1)]]),
					default: C(() => [s("div", F, [
						l(m, { label: "Paid To" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_paid_to,
								"onUpdate:modelValue": n[14] ||= (e) => i.value.tr_paid_to = e,
								placeholder: "Auto-filled from Owner selection"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Lorry Hire" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_lorry_hire_amount,
								"onUpdate:modelValue": n[15] ||= (e) => i.value.tr_lorry_hire_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Add Other Charges" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_other_charges_amount,
								"onUpdate:modelValue": n[16] ||= (e) => i.value.tr_other_charges_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Advance Payment Mode" }, {
							default: C(() => [l(g, {
								modelValue: i.value.tr_advance_payment_method_id,
								"onUpdate:modelValue": n[17] ||= (e) => i.value.tr_advance_payment_method_id = e,
								options: p.value.map((e) => ({
									id: e.id,
									label: e.name
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select Payment Mode"
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(m, { label: "Advance On" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_advance_on,
								"onUpdate:modelValue": n[18] ||= (e) => i.value.tr_advance_on = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Bank" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_advance_bank,
								"onUpdate:modelValue": n[19] ||= (e) => i.value.tr_advance_bank = e,
								list: "lorry-advance-bank-list",
								placeholder: "Type or select Bank"
							}, null, 8, ["modelValue"]), s("datalist", I, [(h(!0), o(e, null, v(w.value, (e) => (h(), o("option", {
								key: e,
								value: e
							}, null, 8, L))), 128))])]),
							_: 1
						}),
						l(m, { label: "Advance Paid Rs" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_advance_amount,
								"onUpdate:modelValue": n[20] ||= (e) => i.value.tr_advance_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Balance / Remaining Amount Rs" }, {
							default: C(() => [l(u, {
								"model-value": i.value.tr_balance_amount,
								readonly: "",
								placeholder: "Auto-calculated (Gross Hire - Advance)"
							}, null, 8, ["model-value"])]),
							_: 1
						}),
						l(m, { label: "Balance Payable at" }, {
							default: C(() => [l(g, {
								modelValue: i.value.tr_balance_payable_at,
								"onUpdate:modelValue": n[21] ||= (e) => i.value.tr_balance_payable_at = e,
								options: f.map((e) => ({
									id: e,
									label: e
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select..."
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(m, { label: "Loaded By" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_loaded_by,
								"onUpdate:modelValue": n[22] ||= (e) => i.value.tr_loaded_by = e
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...n[59] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Final Payment Details", -1)]]),
					default: C(() => [s("div", R, [
						l(m, { label: "Final Paid To" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_final_paid_to,
								"onUpdate:modelValue": n[23] ||= (e) => i.value.tr_final_paid_to = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Add Detention Rs." }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_detention_amount,
								"onUpdate:modelValue": n[24] ||= (e) => i.value.tr_detention_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Extra Hire Rs" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_extra_hire_amount,
								"onUpdate:modelValue": n[25] ||= (e) => i.value.tr_extra_hire_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Other Rs" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_final_other_amount,
								"onUpdate:modelValue": n[26] ||= (e) => i.value.tr_final_other_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Less Adv. at other branch" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_less_advance_other_branch_amount,
								"onUpdate:modelValue": n[27] ||= (e) => i.value.tr_less_advance_other_branch_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Less Deduction for Claims" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_less_deduction_claims_amount,
								"onUpdate:modelValue": n[28] ||= (e) => i.value.tr_less_deduction_claims_amount = e,
								type: "number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Final Balance Amount Paid at" }, {
							default: C(() => [l(g, {
								modelValue: i.value.tr_final_balance_paid_at,
								"onUpdate:modelValue": n[29] ||= (e) => i.value.tr_final_balance_paid_at = e,
								options: f.map((e) => ({
									id: e,
									label: e
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select..."
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(m, { label: "Final Balance Date" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_final_balance_on,
								"onUpdate:modelValue": n[30] ||= (e) => i.value.tr_final_balance_on = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Final Payment Mode" }, {
							default: C(() => [l(g, {
								modelValue: i.value.tr_final_payment_method_id,
								"onUpdate:modelValue": n[31] ||= (e) => i.value.tr_final_payment_method_id = e,
								options: p.value.map((e) => ({
									id: e.id,
									label: e.name
								})),
								"value-prop": "id",
								"label-key": "label",
								placeholder: "Select Payment Mode"
							}, null, 8, ["modelValue", "options"])]),
							_: 1
						}),
						l(m, { label: "Final Bank" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_final_bank,
								"onUpdate:modelValue": n[32] ||= (e) => i.value.tr_final_bank = e,
								list: "lorry-final-bank-list",
								placeholder: "Type or select Bank"
							}, null, 8, ["modelValue"]), s("datalist", z, [(h(!0), o(e, null, v(w.value, (e) => (h(), o("option", {
								key: e,
								value: e
							}, null, 8, B))), 128))])]),
							_: 1
						}),
						l(m, { label: "Net Amount Payable Rs" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_net_amount_payable,
								"onUpdate:modelValue": n[33] ||= (e) => i.value.tr_net_amount_payable = e,
								type: "number",
								readonly: ""
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Final / Remaining Amount Paid Rs" }, {
							default: C(() => [s("div", V, [l(u, {
								modelValue: i.value.tr_final_amount_paid,
								"onUpdate:modelValue": n[34] ||= (e) => i.value.tr_final_amount_paid = e,
								type: "number",
								placeholder: "Enter amount paid"
							}, null, 8, ["modelValue"]), i.value.tr_net_amount_payable ? (h(), o("button", {
								key: 0,
								type: "button",
								class: "absolute end-2 text-xs text-primary-600 hover:text-primary-800 font-medium px-2 py-1 bg-surface rounded border border-line-light cursor-pointer shadow-xs",
								title: "Copy Net Amount Payable",
								onClick: n[35] ||= (e) => i.value.tr_final_amount_paid = i.value.tr_net_amount_payable
							}, " Fill Net ")) : a("", !0)])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...n[60] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Owner Details", -1)]]),
					default: C(() => [s("div", H, [
						l(m, { label: "Owner Name" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_owner_name,
								"onUpdate:modelValue": n[36] ||= (e) => i.value.tr_owner_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Owner Phone No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_owner_phone,
								"onUpdate:modelValue": n[37] ||= (e) => i.value.tr_owner_phone = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Owner Bank Account No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_owner_bank_account_no,
								"onUpdate:modelValue": n[38] ||= (e) => i.value.tr_owner_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Owner PAN No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_owner_pan_no,
								"onUpdate:modelValue": n[39] ||= (e) => i.value.tr_owner_pan_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, {
							label: "Owner Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [l(_, {
								modelValue: i.value.tr_owner_address,
								"onUpdate:modelValue": n[40] ||= (e) => i.value.tr_owner_address = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...n[61] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Driver Details", -1)]]),
					default: C(() => [s("div", U, [
						l(m, { label: "Driver Name" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_driver_name,
								"onUpdate:modelValue": n[41] ||= (e) => i.value.tr_driver_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Driver Licence No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_driver_licence_no,
								"onUpdate:modelValue": n[42] ||= (e) => i.value.tr_driver_licence_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Driver Licence Date" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_driver_licence_date,
								"onUpdate:modelValue": n[43] ||= (e) => i.value.tr_driver_licence_date = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Driver RTO" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_driver_rto_address,
								"onUpdate:modelValue": n[44] ||= (e) => i.value.tr_driver_rto_address = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Driver Valid Up To" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_driver_valid_up_to,
								"onUpdate:modelValue": n[45] ||= (e) => i.value.tr_driver_valid_up_to = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Driver Bank Account No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_driver_bank_account_no,
								"onUpdate:modelValue": n[46] ||= (e) => i.value.tr_driver_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, {
							label: "Driver Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [l(_, {
								modelValue: i.value.tr_driver_address,
								"onUpdate:modelValue": n[47] ||= (e) => i.value.tr_driver_address = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				}),
				l(r, { class: "mb-4" }, {
					header: C(() => [...n[62] ||= [s("h3", { class: "text-base font-semibold text-heading" }, "Broker Details", -1)]]),
					default: C(() => [s("div", W, [
						l(m, { label: "Broker Name" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_broker_name,
								"onUpdate:modelValue": n[48] ||= (e) => i.value.tr_broker_name = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Broker Phone No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_broker_phone,
								"onUpdate:modelValue": n[49] ||= (e) => i.value.tr_broker_phone = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Broker Pan No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_broker_pan_no,
								"onUpdate:modelValue": n[50] ||= (e) => i.value.tr_broker_pan_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Advice Date" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_advice_date,
								"onUpdate:modelValue": n[51] ||= (e) => i.value.tr_advice_date = e,
								type: "date"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, { label: "Broker Bank Account No" }, {
							default: C(() => [l(u, {
								modelValue: i.value.tr_broker_bank_account_no,
								"onUpdate:modelValue": n[52] ||= (e) => i.value.tr_broker_bank_account_no = e
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						l(m, {
							label: "Broker Address",
							class: "md:col-span-2 xl:col-span-3"
						}, {
							default: C(() => [l(_, {
								modelValue: i.value.tr_broker_address,
								"onUpdate:modelValue": n[53] ||= (e) => i.value.tr_broker_address = e,
								rows: "2"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					])]),
					_: 1
				})
			])) : a("", !0);
		};
	}
}), K = {
	key: 0,
	class: "text-center py-8 text-muted"
}, Be = {
	key: 1,
	class: "text-center py-8 text-muted"
}, q = {
	key: 2,
	class: "bg-surface border border-line-default rounded-xl overflow-hidden"
}, Ve = { class: "w-full text-sm" }, J = { class: "px-4 py-3 font-medium text-heading" }, He = { class: "px-4 py-3 text-body" }, Y = { class: "px-4 py-3 text-body" }, X = { class: "px-4 py-3 text-body" }, Ue = { class: "px-4 py-3 text-body" }, We = { class: "px-4 py-3" }, Ge = { class: "px-2 py-1 text-xs rounded-full bg-primary-50 text-primary-600" }, Ke = /* @__PURE__ */ u({
	__name: "CustomerLorryReceiptsView",
	props: { client: { type: [Function, Object] } },
	setup(t) {
		let n = t, r = _([]), i = _(!0);
		m(async () => {
			try {
				let { data: e } = await n.client.get("/api/v1/customer/lorry-receipts");
				r.value = e.data || [];
			} catch {
				r.value = [];
			} finally {
				i.value = !1;
			}
		});
		function a(e) {
			return e ? new Date(e).toLocaleDateString() : "—";
		}
		return (t, n) => (h(), o("div", null, [n[1] ||= s("div", { class: "mb-6" }, [s("h1", { class: "text-2xl font-bold text-heading" }, "My Lorry Receipts"), s("p", { class: "text-sm text-muted mt-1" }, "Truck payment records where you are the owner, driver, or broker")], -1), i.value ? (h(), o("div", K, "Loading...")) : r.value.length === 0 ? (h(), o("div", Be, "No lorry receipts found.")) : (h(), o("div", q, [s("table", Ve, [n[0] ||= s("thead", { class: "bg-surface-secondary border-b border-line-default" }, [s("tr", null, [
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Receipt No"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Truck No"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Owner"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Route"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Date"),
			s("th", { class: "px-4 py-3 text-left font-medium text-muted" }, "Status")
		])], -1), s("tbody", null, [(h(!0), o(e, null, v(r.value, (e) => (h(), o("tr", {
			key: e.id,
			class: "border-b border-line-light"
		}, [
			s("td", J, x(e.invoice_number), 1),
			s("td", He, x(e.tr_lorry_no || "—"), 1),
			s("td", Y, x(e.tr_owner_name || "—"), 1),
			s("td", X, x(e.tr_from_name || "—") + " → " + x(e.tr_to_name || "—"), 1),
			s("td", Ue, x(a(e.invoice_date)), 1),
			s("td", We, [s("span", Ge, x(e.status), 1)])
		]))), 128))])])]))]));
	}
});
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	window.__lorryReceiptClient = n.client, window.__lorryReceiptCan = n.hasAbilities, n.addMessages({ en: { lorry_receipt: {
		title: "Lorry Receipts",
		challan_no: "Challan No.",
		party_name: "Party Name",
		owner_details: "Owner Details",
		driver_details: "Driver Details",
		broker_details: "Broker Details",
		section_c: "Section C: Advance Payment",
		section_e: "Section E: Final Settlement",
		contract_no: "Contract No",
		lorry_no: "Lorry No",
		paid_to: "Paid To",
		lorry_hire_amount: "Lorry Hire Amount",
		advance_amount: "Advance Amount",
		received_bilties: "Received Bilties (Docket Numbers)",
		net_amount_payable: "Net Amount Payable",
		party_profiles: "Party Profiles",
		my_receipts: "My Lorry Receipts"
	} } }), n.registerInvoiceFormSection({
		id: "lorry-receipt-fields",
		component: G
	}), n.registerInvoiceViewMode({
		id: "lorry-receipt-view-mode",
		value: "lorry_receipt",
		label: "Lorry Receipt",
		icon: "TruckIcon",
		createLink: "invoices/create?template=lorry_receipt",
		listLink: "/admin/invoices?view=lorry_receipt",
		ability: "lorry-receipt:view-lorry-receipt",
		columns: [{
			key: "tr_paid_to",
			label: "Paid To"
		}]
	}), n.registerInvoiceDocumentMeta({
		id: "lorry-receipt-doc-meta",
		templateName: "lorry_receipt",
		label: "Lorry Receipt",
		labelPlural: "Lorry Receipts",
		listLink: "/admin/invoices?view=lorry_receipt",
		dateLabel: "Challan Date",
		numberLabel: "Challan No."
	}), n.registerPage({
		id: "customer-lorry-receipts",
		module: "lorry-receipt",
		path: "customer",
		component: Ke,
		meta: { title: "My Lorry Receipts" }
	});
});
//#endregion
