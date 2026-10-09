const { computed: e, createCommentVNode: t, createElementBlock: n, createElementVNode: r, createTextVNode: i, createVNode: a, defineComponent: o, h: s, normalizeClass: c, onMounted: l, onUnmounted: u, openBlock: d, reactive: f, ref: p, resolveComponent: m, toDisplayString: h, withCtx: g, withModifiers: _ } = window.__invoiceshelf_vue;
//#region resources/js/components/AccessRequestOverlay.vue?vue&type=script&setup=true&lang.ts
var v = { key: 0 }, y = { class: "px-8 py-8 sm:p-6" }, b = { class: "flex justify-end gap-2 px-8 py-8 sm:px-6 sm:py-6 bg-surface-secondary" }, x = /* @__PURE__ */ o({
	__name: "AccessRequestOverlay",
	setup(e) {
		let o = p(!1), s = p(!1), h = p(!1), x = p("the company owner"), S = f({
			subject: "",
			message: ""
		});
		function C() {
			return window.__accessRequestClient;
		}
		function w(e) {
			let t = e;
			S.subject = t.detail.subject, S.message = t.detail.message, x.value = t.detail.recipient, o.value = !0, s.value = !0;
		}
		function T() {
			s.value = !1;
		}
		async function E() {
			if (!(!S.subject.trim() || !S.message.trim())) {
				h.value = !0;
				try {
					let e = C();
					if (!e) {
						console.error("AccessRequest: API client not available");
						return;
					}
					let t = await e.post("/api/v1/access-request", {
						subject: S.subject,
						message: S.message
					});
					t.data?.error ? window.dispatchEvent(new CustomEvent("access-request:error", { detail: t.data.error })) : (s.value = !1, window.dispatchEvent(new CustomEvent("access-request:success", { detail: "Request sent to the company owner" })));
				} catch {
					window.dispatchEvent(new CustomEvent("access-request:error", { detail: "Could not send the request" }));
				} finally {
					h.value = !1;
				}
			}
		}
		return l(() => {
			window.addEventListener("access-request:open", w);
		}), u(() => {
			window.removeEventListener("access-request:open", w);
		}), (e, l) => {
			let u = m("BaseInput"), f = m("BaseInputGroup"), p = m("BaseTextarea"), C = m("BaseInputGrid"), w = m("BaseButton"), D = m("BaseIcon"), O = m("BaseModal");
			return o.value ? (d(), n("div", v, [a(O, {
				show: s.value,
				closable: "",
				onClose: T
			}, {
				header: g(() => [...l[3] ||= [i(" Request Access ", -1)]]),
				default: g(() => [r("form", { onSubmit: l[2] ||= _(() => {}, ["prevent"]) }, [r("div", y, [a(C, { layout: "one-column" }, {
					default: g(() => [
						a(f, { label: "To" }, {
							default: g(() => [a(u, {
								"model-value": x.value,
								type: "text",
								disabled: ""
							}, null, 8, ["model-value"])]),
							_: 1
						}),
						a(f, {
							label: "Subject",
							required: ""
						}, {
							default: g(() => [a(u, {
								modelValue: S.subject,
								"onUpdate:modelValue": l[0] ||= (e) => S.subject = e,
								type: "text"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						a(f, {
							label: "Message",
							required: ""
						}, {
							default: g(() => [a(p, {
								modelValue: S.message,
								"onUpdate:modelValue": l[1] ||= (e) => S.message = e,
								rows: "7"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					]),
					_: 1
				})]), r("div", b, [a(w, {
					variant: "white",
					onClick: T
				}, {
					default: g(() => [...l[4] ||= [i(" Cancel ", -1)]]),
					_: 1
				}), a(w, {
					disabled: h.value,
					variant: "primary",
					onClick: E
				}, {
					left: g((e) => [a(D, {
						name: "PaperAirplaneIcon",
						class: c(e.class)
					}, null, 8, ["class"])]),
					default: g(() => [l[5] ||= i(" Send ", -1)]),
					_: 1
				}, 8, ["disabled"])])], 32)]),
				_: 1
			}, 8, ["show"])])) : t("", !0);
		};
	}
}), S = { class: "flex flex-col items-center justify-center gap-4 py-16 text-center" }, C = { class: "flex items-center justify-center w-14 h-14 rounded-full bg-surface-secondary" }, w = { class: "text-lg font-semibold text-heading" }, T = { class: "max-w-md mt-1 text-sm text-muted" }, E = { class: "flex items-center gap-2" }, D = /* @__PURE__ */ o({
	__name: "PermissionMissingUI",
	props: { viewModeLabel: {} },
	setup(t) {
		let o = t, s = e(() => o.viewModeLabel ?? "Receipt"), l = e(() => [
			"Hello,",
			"",
			`I need access to ${s.value}s.`,
			"",
			"Could you grant me the permission to view them? You can do this under Settings → Roles → my role.",
			"",
			"Thank you,"
		].join("\n"));
		function u() {
			window.dispatchEvent(new CustomEvent("access-request:open", { detail: {
				subject: `Request: Access to ${s.value}s`,
				message: l.value,
				recipient: null
			} }));
		}
		async function f() {
			try {
				await navigator.clipboard.writeText(l.value), window.dispatchEvent(new CustomEvent("access-request:success", { detail: "Request copied — paste it to the owner in chat or email" }));
			} catch {
				window.dispatchEvent(new CustomEvent("access-request:error", { detail: "Could not copy the request" }));
			}
		}
		return (e, t) => {
			let o = m("BaseIcon"), l = m("BaseButton");
			return d(), n("div", S, [
				r("span", C, [a(o, {
					name: "LockClosedIcon",
					class: "w-7 h-7 text-muted"
				})]),
				r("div", null, [r("h2", w, h(s.value) + "s", 1), r("p", T, " You don't have permission to view " + h(s.value) + "s. Ask the company owner for access. ", 1)]),
				r("div", E, [a(l, {
					variant: "primary",
					onClick: u
				}, {
					left: g((e) => [a(o, {
						name: "EnvelopeIcon",
						class: c(e.class)
					}, null, 8, ["class"])]),
					default: g(() => [t[0] ||= i(" Request Access ", -1)]),
					_: 1
				}), a(l, {
					variant: "white",
					onClick: f
				}, {
					left: g((e) => [a(o, {
						name: "ClipboardDocumentIcon",
						class: c(e.class)
					}, null, 8, ["class"])]),
					default: g(() => [t[1] ||= i(" Copy Request ", -1)]),
					_: 1
				})])
			]);
		};
	}
});
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	window.__accessRequestClient = n.client, n.registerCompanyLayoutOverlay({
		id: "access-request-overlay",
		component: o({ setup: () => () => s(x) })
	}), window.addEventListener("access-request:success", (e) => {
		let t = e.detail;
		n.notify("success", t);
	}), window.addEventListener("access-request:error", (e) => {
		let t = e.detail;
		n.notify("error", t);
	}), n.registerInvoicePermissionMissing({
		id: "access-request-permission-missing",
		component: D
	});
});
//#endregion
