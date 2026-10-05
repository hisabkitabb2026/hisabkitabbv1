const { createCommentVNode: e, createElementBlock: t, createElementVNode: n, createTextVNode: r, createVNode: i, defineComponent: a, h: o, normalizeClass: s, onMounted: c, onUnmounted: l, openBlock: u, reactive: d, ref: f, resolveComponent: p, withCtx: m, withModifiers: h } = window.__invoiceshelf_vue;
//#region resources/js/components/AccessRequestOverlay.vue?vue&type=script&setup=true&lang.ts
var g = { key: 0 }, _ = { class: "px-8 py-8 sm:p-6" }, v = { class: "flex justify-end gap-2 px-8 py-8 sm:px-6 sm:py-6 bg-surface-secondary" }, y = /* @__PURE__ */ a({
	__name: "AccessRequestOverlay",
	setup(a) {
		let o = f(!1), y = f(!1), b = f(!1), x = f("the company owner"), S = d({
			subject: "",
			message: ""
		});
		function C() {
			return window.__accessRequestClient;
		}
		function w(e) {
			let t = e;
			S.subject = t.detail.subject, S.message = t.detail.message, x.value = t.detail.recipient, o.value = !0, y.value = !0;
		}
		function T() {
			y.value = !1;
		}
		async function E() {
			if (!(!S.subject.trim() || !S.message.trim())) {
				b.value = !0;
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
					t.data?.error ? window.dispatchEvent(new CustomEvent("access-request:error", { detail: t.data.error })) : (y.value = !1, window.dispatchEvent(new CustomEvent("access-request:success", { detail: "Request sent to the company owner" })));
				} catch {
					window.dispatchEvent(new CustomEvent("access-request:error", { detail: "Could not send the request" }));
				} finally {
					b.value = !1;
				}
			}
		}
		return c(() => {
			window.addEventListener("access-request:open", w);
		}), l(() => {
			window.removeEventListener("access-request:open", w);
		}), (a, c) => {
			let l = p("BaseInput"), d = p("BaseInputGroup"), f = p("BaseTextarea"), C = p("BaseInputGrid"), w = p("BaseButton"), D = p("BaseIcon"), O = p("BaseModal");
			return o.value ? (u(), t("div", g, [i(O, {
				show: y.value,
				closable: "",
				onClose: T
			}, {
				header: m(() => [...c[3] ||= [r(" Request Access ", -1)]]),
				default: m(() => [n("form", { onSubmit: c[2] ||= h(() => {}, ["prevent"]) }, [n("div", _, [i(C, { layout: "one-column" }, {
					default: m(() => [
						i(d, { label: "To" }, {
							default: m(() => [i(l, {
								"model-value": x.value,
								type: "text",
								disabled: ""
							}, null, 8, ["model-value"])]),
							_: 1
						}),
						i(d, {
							label: "Subject",
							required: ""
						}, {
							default: m(() => [i(l, {
								modelValue: S.subject,
								"onUpdate:modelValue": c[0] ||= (e) => S.subject = e,
								type: "text"
							}, null, 8, ["modelValue"])]),
							_: 1
						}),
						i(d, {
							label: "Message",
							required: ""
						}, {
							default: m(() => [i(f, {
								modelValue: S.message,
								"onUpdate:modelValue": c[1] ||= (e) => S.message = e,
								rows: "7"
							}, null, 8, ["modelValue"])]),
							_: 1
						})
					]),
					_: 1
				})]), n("div", v, [i(w, {
					variant: "white",
					onClick: T
				}, {
					default: m(() => [...c[4] ||= [r(" Cancel ", -1)]]),
					_: 1
				}), i(w, {
					disabled: b.value,
					variant: "primary",
					onClick: E
				}, {
					left: m((e) => [i(D, {
						name: "PaperAirplaneIcon",
						class: s(e.class)
					}, null, 8, ["class"])]),
					default: m(() => [c[5] ||= r(" Send ", -1)]),
					_: 1
				}, 8, ["disabled"])])], 32)]),
				_: 1
			}, 8, ["show"])])) : e("", !0);
		};
	}
});
//#endregion
//#region resources/js/init.ts
window.InvoiceShelf.booting((e, t, n) => {
	window.__accessRequestClient = n.client, n.registerCompanyLayoutOverlay({
		id: "access-request-overlay",
		component: a({ setup: () => () => o(y) })
	}), window.addEventListener("access-request:success", (e) => {
		let t = e.detail;
		n.notify("success", t);
	}), window.addEventListener("access-request:error", (e) => {
		let t = e.detail;
		n.notify("error", t);
	});
});
//#endregion
