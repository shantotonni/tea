<script setup>
import { ref, reactive, onMounted } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import Skeleton from '../components/Skeleton.vue'
import {
    fetchBlendQuestions, createBlendQuestion, updateBlendQuestion, deleteBlendQuestion,
    createBlendOption, updateBlendOption, deleteBlendOption,
} from '../data'
import { toast } from '../composables/useToast'

const questions = ref([])
const loading = ref(true)

async function load() {
    loading.value = true
    try {
        questions.value = await fetchBlendQuestions()
    } finally {
        loading.value = false
    }
}
onMounted(load)

/* ---- question modal ---- */
const qBlank = () => ({ key: '', label: '', is_published: true, sort_order: 0 })
const qModal = reactive({ open: false, id: null })
const qBusy = ref(false)
const qErrors = ref({})
const qForm = reactive(qBlank())

function qCreate() {
    qModal.open = true; qModal.id = null
    qErrors.value = {}
    Object.assign(qForm, qBlank(), { sort_order: questions.value.length })
}
function qEdit(q) {
    qModal.open = true; qModal.id = q.id
    qErrors.value = {}
    Object.assign(qForm, { key: q.key, label: q.label, is_published: !!q.is_published, sort_order: q.sort_order || 0 })
}
async function qSave() {
    qBusy.value = true; qErrors.value = {}
    try {
        if (qModal.id) { await updateBlendQuestion(qModal.id, { ...qForm }); toast.success('Question updated.') }
        else { await createBlendQuestion({ ...qForm }); toast.success('Question added.') }
        qModal.open = false
        await load()
    } catch (e) {
        qErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        qBusy.value = false
    }
}
async function qToggle(q) {
    try {
        await updateBlendQuestion(q.id, { key: q.key, label: q.label, sort_order: q.sort_order, is_published: !q.is_published })
        q.is_published = !q.is_published
    } catch (e) { /* api.js toasts */ }
}
const qConfirm = ref(null)
async function qRemove() {
    if (!qConfirm.value) return
    qBusy.value = true
    try {
        await deleteBlendQuestion(qConfirm.value.id)
        toast.success('Question deleted.')
        qConfirm.value = null
        await load()
    } finally {
        qBusy.value = false
    }
}

/* ---- option modal ---- */
const oBlank = () => ({ question_id: null, opt_id: '', title: '', hint: '', icon: '', sort_order: 0 })
const oModal = reactive({ open: false, id: null })
const oBusy = ref(false)
const oErrors = ref({})
const oForm = reactive(oBlank())

function oCreate(q) {
    oModal.open = true; oModal.id = null
    oErrors.value = {}
    Object.assign(oForm, oBlank(), { question_id: q.id, sort_order: (q.options || []).length })
}
function oEdit(o) {
    oModal.open = true; oModal.id = o.id
    oErrors.value = {}
    Object.assign(oForm, { question_id: o.question_id, opt_id: o.opt_id, title: o.title, hint: o.hint || '', icon: o.icon || '', sort_order: o.sort_order || 0 })
}
async function oSave() {
    oBusy.value = true; oErrors.value = {}
    try {
        if (oModal.id) { await updateBlendOption(oModal.id, { ...oForm }); toast.success('Option updated.') }
        else { await createBlendOption({ ...oForm }); toast.success('Option added.') }
        oModal.open = false
        await load()
    } catch (e) {
        oErrors.value = e.data?.errors || {}
        if (e.status === 422) toast.error('Please fix the highlighted fields.')
    } finally {
        oBusy.value = false
    }
}
const oConfirm = ref(null)
async function oRemove() {
    if (!oConfirm.value) return
    oBusy.value = true
    try {
        await deleteBlendOption(oConfirm.value.id)
        toast.success('Option deleted.')
        oConfirm.value = null
        await load()
    } finally {
        oBusy.value = false
    }
}
</script>

<template>
    <div class="page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Storefront</p>
                <h2>Blend Finder</h2>
                <p>The 3-question quiz that recommends a blend. Manage the questions and their answer options here.</p>
            </div>
            <div class="head-actions">
                <button class="btn btn-primary" @click="qCreate"><AppIcon name="plus" :size="17" /> Add Question</button>
            </div>
        </div>

        <div v-if="loading" class="card" style="padding: 1.4rem"><Skeleton :rows="6" /></div>

        <template v-else>
            <p v-if="!questions.length" class="card" style="padding: 1.4rem; color: var(--muted)">No questions yet.</p>

            <section v-for="(q, qi) in questions" :key="q.id" class="card blk">
                <header class="blk-head">
                    <h3>
                        <span class="q-num">{{ qi + 1 }}</span>
                        {{ q.label }}
                        <code class="q-key">{{ q.key }}</code>
                    </h3>
                    <div class="blk-tools">
                        <button class="switch sm" :class="{ on: q.is_published }" title="Published" @click="qToggle(q)" />
                        <button class="icon-btn" title="Edit question" @click="qEdit(q)"><AppIcon name="edit" :size="16" /></button>
                        <button class="icon-btn" title="Delete question" @click="qConfirm = q"><AppIcon name="trash" :size="16" /></button>
                    </div>
                </header>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th style="width: 50px">Icon</th><th>Title</th><th>Hint</th><th style="width: 120px">Answer id</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="o in q.options" :key="o.id">
                                <td style="font-size: 1.2rem">{{ o.icon }}</td>
                                <td><strong>{{ o.title }}</strong></td>
                                <td><span style="color: var(--muted)">{{ o.hint }}</span></td>
                                <td><code>{{ o.opt_id }}</code></td>
                                <td><div class="row-actions">
                                    <button title="Edit" @click="oEdit(o)"><AppIcon name="edit" :size="16" /></button>
                                    <button title="Delete" @click="oConfirm = o"><AppIcon name="trash" :size="16" /></button>
                                </div></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="blk-foot">
                        <button class="btn btn-ghost btn-sm" @click="oCreate(q)"><AppIcon name="plus" :size="15" /> Add option</button>
                    </div>
                </div>
            </section>

            <p class="hint-note">
                Note: which blend each answer maps to (and the recommendation blurb) is decision logic tied to product slugs — it lives in the storefront, not here. This screen controls the questions and options shoppers see.
            </p>
        </template>

        <!-- question modal -->
        <Transition name="modal">
            <div v-if="qModal.open" class="modal-wrap" @click.self="!qBusy && (qModal.open = false)">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ qModal.id ? 'Edit question' : 'Add question' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="!qBusy && (qModal.open = false)">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <label class="field" :class="{ invalid: qErrors.label }">
                            <span>Question label</span>
                            <input v-model="qForm.label" type="text" placeholder="When do you reach for tea?" />
                            <em v-if="qErrors.label" class="field-msg">{{ qErrors.label[0] }}</em>
                        </label>
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: qErrors.key }">
                                <span>Key <em style="color: var(--muted)">(unique slug)</em></span>
                                <input v-model="qForm.key" type="text" placeholder="time" />
                                <em v-if="qErrors.key" class="field-msg">{{ qErrors.key[0] }}</em>
                            </label>
                            <label class="field">
                                <span>Sort order</span>
                                <input v-model.number="qForm.sort_order" type="number" min="0" />
                            </label>
                        </div>
                        <div class="modal-toggles">
                            <label class="mini-check"><input v-model="qForm.is_published" type="checkbox" /> <span>Show on storefront</span></label>
                        </div>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="qBusy" @click="qModal.open = false">Cancel</button>
                        <button class="btn btn-primary" :disabled="qBusy" @click="qSave">{{ qBusy ? 'Saving…' : qModal.id ? 'Save changes' : 'Add question' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- option modal -->
        <Transition name="modal">
            <div v-if="oModal.open" class="modal-wrap" @click.self="!oBusy && (oModal.open = false)">
                <div class="modal">
                    <header class="modal-head">
                        <h3>{{ oModal.id ? 'Edit option' : 'Add option' }}</h3>
                        <button class="modal-x" aria-label="Close" @click="!oBusy && (oModal.open = false)">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                        </button>
                    </header>
                    <div class="modal-body">
                        <div class="form-grid">
                            <label class="field" :class="{ invalid: oErrors.icon }">
                                <span>Icon (emoji)</span>
                                <input v-model="oForm.icon" type="text" maxlength="4" placeholder="🌅" />
                                <em v-if="oErrors.icon" class="field-msg">{{ oErrors.icon[0] }}</em>
                            </label>
                            <label class="field" :class="{ invalid: oErrors.opt_id }">
                                <span>Answer id <em style="color: var(--muted)">(slug)</em></span>
                                <input v-model="oForm.opt_id" type="text" placeholder="morning" />
                                <em v-if="oErrors.opt_id" class="field-msg">{{ oErrors.opt_id[0] }}</em>
                            </label>
                        </div>
                        <label class="field" :class="{ invalid: oErrors.title }">
                            <span>Title</span>
                            <input v-model="oForm.title" type="text" placeholder="Morning" />
                            <em v-if="oErrors.title" class="field-msg">{{ oErrors.title[0] }}</em>
                        </label>
                        <label class="field" :class="{ invalid: oErrors.hint }">
                            <span>Hint</span>
                            <input v-model="oForm.hint" type="text" placeholder="to wake up properly" />
                            <em v-if="oErrors.hint" class="field-msg">{{ oErrors.hint[0] }}</em>
                        </label>
                        <label class="field">
                            <span>Sort order</span>
                            <input v-model.number="oForm.sort_order" type="number" min="0" />
                        </label>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="oBusy" @click="oModal.open = false">Cancel</button>
                        <button class="btn btn-primary" :disabled="oBusy" @click="oSave">{{ oBusy ? 'Saving…' : oModal.id ? 'Save changes' : 'Add option' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>

        <!-- delete confirms -->
        <Transition name="modal">
            <div v-if="qConfirm" class="modal-wrap" @click.self="qConfirm = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this question?</h3>
                        <p>“{{ qConfirm.label }}” and all its options — this cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="qBusy" @click="qConfirm = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="qBusy" @click="qRemove">{{ qBusy ? 'Deleting…' : 'Delete' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
        <Transition name="modal">
            <div v-if="oConfirm" class="modal-wrap" @click.self="oConfirm = null">
                <div class="modal modal-sm">
                    <div class="modal-body confirm-body">
                        <span class="confirm-ico"><AppIcon name="trash" :size="22" /></span>
                        <h3>Delete this option?</h3>
                        <p>“{{ oConfirm.title }}” — this cannot be undone.</p>
                    </div>
                    <footer class="modal-foot">
                        <button class="btn btn-ghost" :disabled="oBusy" @click="oConfirm = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="oBusy" @click="oRemove">{{ oBusy ? 'Deleting…' : 'Delete' }}</button>
                    </footer>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.blk { margin-bottom: 1.4rem; }
.blk-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line, #eee);
}
.blk-head h3 { margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 0.6rem; }
.blk-tools { display: flex; align-items: center; gap: 0.4rem; }
.q-num {
    display: inline-grid; place-items: center; width: 26px; height: 26px; border-radius: 50%;
    background: color-mix(in srgb, var(--gold) 22%, transparent); color: var(--gold-ink, #8a6d1f);
    font-size: 0.82rem; font-weight: 700;
}
.q-key, code { font-size: 0.8rem; color: var(--muted); background: var(--soft, #f3f3f3); padding: 0.05rem 0.4rem; border-radius: 5px; }
.blk-foot { padding: 0.9rem 1.4rem; border-top: 1px solid var(--line, #eee); }
.btn-sm { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
.hint-note { color: var(--muted); font-size: 0.85rem; padding: 0.5rem 0.2rem; line-height: 1.5; }
</style>
