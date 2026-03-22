<template>
  <AdminLayout>
    <div class="qualification-scripts-page space-y-1">
      <div class="top-card rounded-2xl border border-slate-200 bg-white px-4 py-1.5 shadow-sm">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">Qualification Scripts</h1>
          </div>

          <div class="grid w-full gap-2 sm:grid-cols-[minmax(220px,1fr)_170px_auto] xl:max-w-[860px]">
            <input
                v-model="q"
                class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-slate-400"
                placeholder="Search script"
            />

            <select
                v-model="statusFilter"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-400"
            >
              <option value="all">All scripts</option>
              <option value="active">Active only</option>
              <option value="inactive">Inactive only</option>
            </select>

            <button
                class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white"
                @click="openCreate"
            >
              Add
            </button>
          </div>
        </div>
      </div>

      <div
          v-if="err"
          class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700 shadow-sm"
      >
        {{ err }}
      </div>

      <div
          v-if="successMessage"
          class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 shadow-sm"
      >
        {{ successMessage }}
      </div>

      <div class="space-y-2 md:hidden">
        <div
            v-for="row in mobileRows"
            :key="row.id"
            class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm"
        >
          <div class="flex items-start justify-between gap-2">
            <button
                class="text-left text-sm font-semibold leading-5 text-sky-700 hover:underline"
                @click="openBuilderForRow(row)"
            >
              {{ row.name || '—' }}
            </button>

            <div class="flex flex-wrap justify-end gap-1">
              <span class="script-badge" :class="row.is_active ? 'script-badge--on' : 'script-badge--off'">
                {{ row.is_active ? 'Active' : 'Inactive' }}
              </span>
              <span
                  v-if="row.is_default"
                  class="script-badge script-badge--default"
              >
                Default
              </span>
            </div>
          </div>

          <div class="mt-2 grid grid-cols-1 gap-1 text-sm leading-5 text-slate-700">
            <div><span class="font-medium">Slug:</span> {{ row.slug || '—' }}</div>
            <div><span class="font-medium">Platform:</span> {{ row.applies_to_platform || '—' }}</div>
            <div><span class="font-medium">Ad:</span> {{ row.applies_to_ad_name || '—' }}</div>
            <div><span class="font-medium">Version:</span> {{ row.version || 1 }}</div>
            <div><span class="font-medium">Priority:</span> {{ row.priority ?? 0 }}</div>
            <div><span class="font-medium">Questions:</span> {{ row.steps_count ?? 0 }}</div>
          </div>

          <div class="mt-3">
            <button
                class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm text-slate-700"
                @click="openBuilderForRow(row)"
            >
              Open
            </button>
          </div>
        </div>

        <div
            v-if="!loading && !filteredRows.length"
            class="rounded-2xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-500 shadow-sm"
        >
          No qualification scripts found.
        </div>

        <div
            v-if="filteredRows.length && mobileTotalPages > 1"
            class="rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm"
        >
          <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
            <div>Page {{ mobilePage }} of {{ mobileTotalPages }}</div>

            <div class="flex items-center gap-2">
              <button
                  class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="mobilePage <= 1"
                  @click="goToMobilePage(mobilePage - 1)"
              >
                Prev
              </button>

              <button
                  class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="mobilePage >= mobileTotalPages"
                  @click="goToMobilePage(mobilePage + 1)"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
          ref="tableWrap"
          class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block"
      >
        <DataTable
            class="display nowrap compact stripe row-border hover qualification-scripts-datatable w-full"
            :data="filteredRows"
            :columns="columns"
            :options="options"
        />
      </div>

      <div
          v-if="builderOpen && selectedScriptId"
          class="rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-200 px-3 py-2">
          <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-slate-900">Script Builder</div>
              <div class="truncate text-xs text-slate-500">
                {{ selectedScriptSummary?.name || 'Selected script' }}
              </div>
            </div>

            <div class="flex flex-wrap gap-1.5">
              <button
                  type="button"
                  class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700"
                  @click="editRow(selectedScriptSummary)"
              >
                Edit Script
              </button>

              <button
                  type="button"
                  class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700"
                  @click="cloneRow(selectedScriptSummary)"
              >
                Clone
              </button>

              <button
                  type="button"
                  class="rounded-lg border border-rose-300 px-2.5 py-1 text-xs font-medium text-rose-700 disabled:opacity-50"
                  :disabled="deleting"
                  @click="deleteSelectedScriptFromBuilder"
              >
                {{ deleting ? 'Deleting...' : 'Delete' }}
              </button>

              <button
                  type="button"
                  class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700"
                  @click="closeBuilder"
              >
                Close
              </button>

              <button
                  type="button"
                  class="rounded-lg bg-slate-900 px-3 py-1 text-xs font-semibold text-white disabled:opacity-50"
                  :disabled="builderSaving || builderLoading"
                  @click="saveBuilderNow"
              >
                {{ builderSaving ? 'Saving...' : 'Save' }}
              </button>
            </div>
          </div>
        </div>

        <div class="p-3">
          <div
              v-if="builderErr"
              class="mb-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
          >
            {{ builderErr }}
          </div>

          <div
              v-if="builderSuccessMessage"
              class="mb-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700"
          >
            {{ builderSuccessMessage }}
          </div>

          <div
              v-if="builderLoading"
              class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"
          >
            Loading script builder...
          </div>

          <div v-else class="grid gap-3 xl:grid-cols-[280px_minmax(0,1fr)]">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2.5">
              <div class="mb-2 flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Questions</div>
                  <div class="text-[11px] text-slate-500">
                    Open any step directly
                  </div>
                </div>

                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700"
                    @click="addStep"
                >
                  Add
                </button>
              </div>

              <div class="mb-2 rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-[11px] text-slate-700">
                <div><span class="font-medium">Match:</span> {{ buildMatchSummary(selectedScriptSummary) }}</div>
                <div class="mt-1 flex flex-wrap gap-1">
                  <span
                      class="script-badge"
                      :class="selectedScriptSummary?.is_active ? 'script-badge--on' : 'script-badge--off'"
                  >
                    {{ selectedScriptSummary?.is_active ? 'Active' : 'Inactive' }}
                  </span>
                  <span
                      v-if="selectedScriptSummary?.is_default"
                      class="script-badge script-badge--default"
                  >
                    Default
                  </span>
                  <span class="script-badge script-badge--neutral">
                    {{ builder.steps.length }} {{ builder.steps.length === 1 ? 'Q' : 'Qs' }}
                  </span>
                </div>
              </div>

              <div
                  v-if="!builder.steps.length"
                  class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-4 text-xs text-slate-500"
              >
                No questions yet.
              </div>

              <div v-else class="space-y-2">
                <div class="flex items-center justify-between gap-2">
                  <div class="text-[11px] text-slate-500">
                    Page {{ stepTabsPage }} of {{ stepTabsTotalPages }}
                  </div>

                  <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-[11px] font-medium text-slate-700 disabled:opacity-50"
                        :disabled="stepTabsPage <= 1"
                        @click="goToStepTabsPage(stepTabsPage - 1)"
                    >
                      Prev
                    </button>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-[11px] font-medium text-slate-700 disabled:opacity-50"
                        :disabled="stepTabsPage >= stepTabsTotalPages"
                        @click="goToStepTabsPage(stepTabsPage + 1)"
                    >
                      Next
                    </button>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <button
                      v-for="item in pagedStepTabs"
                      :key="item.step._tmpKey"
                      type="button"
                      class="w-full rounded-xl border px-2.5 py-2 text-left transition"
                      :class="item.index === activeStepIndex
                      ? 'border-slate-900 bg-slate-900 text-white'
                      : 'border-slate-200 bg-white text-slate-800 hover:bg-slate-50'"
                      @click="selectStep(item.index)"
                  >
                    <div class="flex items-center justify-between gap-2">
                      <div class="min-w-0 truncate text-xs font-semibold">
                        STEP {{ item.index + 1 }} | {{ item.step.title || item.step.prompt_text || item.step.step_key || 'Untitled question' }}
                      </div>

                      <div class="shrink-0">
                        <span
                            class="inline-flex rounded-full border px-1.5 py-0.5 text-[10px] font-semibold"
                            :class="item.index === activeStepIndex
                            ? 'border-white/30 bg-white/10 text-white'
                            : 'border-slate-200 bg-slate-50 text-slate-600'"
                        >
                          {{ item.step.options.length }}
                        </span>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <div class="min-w-0">
              <div
                  v-if="!currentStep"
                  class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-xs text-slate-500"
              >
                Select a question on the left to edit it.
              </div>

              <div
                  v-else
                  class="rounded-2xl border border-slate-200 bg-white shadow-sm"
              >
                <div class="border-b border-slate-200 px-3 py-2">
                  <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                      <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Question {{ activeStepIndex + 1 }} of {{ builder.steps.length }}
                      </div>
                      <div class="truncate text-sm font-semibold text-slate-900">
                        {{ currentStep.title || currentStep.prompt_text || currentStep.step_key || 'Untitled question' }}
                      </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                      <button
                          type="button"
                          class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 disabled:opacity-50"
                          :disabled="activeStepIndex === 0"
                          @click="selectStep(activeStepIndex - 1)"
                      >
                        Prev
                      </button>

                      <button
                          type="button"
                          class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 disabled:opacity-50"
                          :disabled="activeStepIndex >= builder.steps.length - 1"
                          @click="selectStep(activeStepIndex + 1)"
                      >
                        Next
                      </button>

                      <button
                          type="button"
                          class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 disabled:opacity-50"
                          :disabled="activeStepIndex === 0"
                          @click="moveStep(activeStepIndex, -1)"
                      >
                        Up
                      </button>

                      <button
                          type="button"
                          class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 disabled:opacity-50"
                          :disabled="activeStepIndex >= builder.steps.length - 1"
                          @click="moveStep(activeStepIndex, 1)"
                      >
                        Down
                      </button>

                      <button
                          type="button"
                          class="rounded-lg border border-rose-300 px-2.5 py-1 text-xs font-medium text-rose-700"
                          @click="removeStep(activeStepIndex)"
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                </div>

                <div class="space-y-2 p-3">
                  <div class="grid grid-cols-1 gap-2 xl:grid-cols-4">
                    <div class="xl:col-span-1">
                      <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Step Key</span>
                        <FieldHelpPopover field="stepKey" />
                      </label>
                      <input
                          v-model="currentStep.step_key"
                          class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                          placeholder="e.g. final_call_booked"
                      />
                    </div>

                    <div class="xl:col-span-2">
                      <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Title</span>
                        <FieldHelpPopover field="title" />
                      </label>
                      <input
                          v-model="currentStep.title"
                          class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                          placeholder="Short internal title"
                      />
                    </div>

                    <div class="xl:col-span-1">
                      <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Order</span>
                        <FieldHelpPopover field="order" align="right" />
                      </label>
                      <input
                          v-model="currentStep.step_order"
                          type="number"
                          min="1"
                          class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                      />
                    </div>

                    <div class="xl:col-span-3">
                      <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Prompt Text</span>
                        <FieldHelpPopover field="promptText" />
                      </label>
                      <textarea
                          v-model="currentStep.prompt_text"
                          rows="2"
                          class="w-full resize-y rounded-lg border border-slate-300 px-2.5 py-2 text-xs"
                          placeholder="Question shown to the rep"
                      />
                    </div>

                    <div class="xl:col-span-1">
                      <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Input Type</span>
                        <FieldHelpPopover field="inputType" align="right" />
                      </label>
                      <select
                          v-model="currentStep.step_type"
                          class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                      >
                        <option value="single_choice">Single Choice</option>
                        <option value="boolean">Yes / No</option>
                        <option value="text">Text</option>
                        <option value="textarea">Long Text</option>
                        <option value="number">Number</option>
                      </select>
                    </div>

                    <div class="xl:col-span-4">
                      <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Help Text</span>
                        <FieldHelpPopover field="helpText" />
                      </label>
                      <textarea
                          v-model="currentStep.help_text"
                          rows="2"
                          class="w-full resize-y rounded-lg border border-slate-300 px-2.5 py-2 text-xs"
                          placeholder="Optional instruction for the caller"
                      />
                    </div>
                  </div>

                  <div class="grid grid-cols-1 gap-2 xl:grid-cols-4">
                    <div>
                      <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Recommended Status</span>
                        <FieldHelpPopover field="recommendedStatusStep" />
                      </label>
                      <input
                          v-model="currentStep.recommended_status"
                          class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                          placeholder="qualified / disqualified / contacted"
                      />
                    </div>

                    <div>
                      <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Recommended Stage Order</span>
                        <FieldHelpPopover field="recommendedStageOrderStep" />
                      </label>
                      <input
                          v-model="currentStep.recommended_stage_order"
                          type="number"
                          min="1"
                          class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                          placeholder="e.g. 2"
                      />
                    </div>

                    <div class="flex items-end">
                      <div class="flex items-center gap-1.5">
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                          <input v-model="currentStep.is_required" type="checkbox" />
                          <span>Required</span>
                        </label>
                        <FieldHelpPopover field="required" />
                      </div>
                    </div>

                    <div class="flex items-end gap-3">
                      <div class="flex items-center gap-1.5">
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                          <input v-model="currentStep.is_terminal" type="checkbox" />
                          <span>Terminal</span>
                        </label>
                        <FieldHelpPopover field="terminal" />
                      </div>

                      <div class="flex items-center gap-1.5">
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                          <input v-model="currentStep.is_disqualifying" type="checkbox" />
                          <span>Disqualify</span>
                        </label>
                        <FieldHelpPopover field="disqualifyStep" align="right" />
                      </div>
                    </div>
                  </div>

                  <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                    <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div class="min-w-0">
                        <div class="text-xs font-semibold text-slate-900">Answer Options</div>
                        <div class="text-[11px] text-slate-500">
                          Branching, score, outcome
                        </div>
                      </div>

                      <button
                          type="button"
                          class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700"
                          @click="addOption(currentStep)"
                      >
                        Add Option
                      </button>
                    </div>

                    <div
                        v-if="!currentStep.options.length"
                        class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-3 text-xs text-slate-500"
                    >
                      No options yet.
                    </div>

                    <div v-else class="space-y-2">
                      <div
                          v-for="(option, optionIndex) in currentStep.options"
                          :key="option._tmpKey"
                          class="rounded-xl border border-slate-200 bg-white p-2.5"
                      >
                        <div class="mb-2 flex items-center justify-between gap-2">
                          <div class="text-xs font-semibold text-slate-900">
                            Option {{ optionIndex + 1 }}
                          </div>

                          <button
                              type="button"
                              class="rounded-lg border border-rose-300 px-2.5 py-1 text-xs font-medium text-rose-700"
                              @click="removeOption(currentStep, optionIndex)"
                          >
                            Remove
                          </button>
                        </div>

                        <div class="grid grid-cols-1 gap-2 xl:grid-cols-4">
                          <div>
                            <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                              <span>Label</span>
                              <FieldHelpPopover field="optionLabel" />
                            </label>
                            <input
                                v-model="option.label"
                                class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                                placeholder="Shown to rep"
                            />
                          </div>

                          <div>
                            <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                              <span>Value</span>
                              <FieldHelpPopover field="optionValue" />
                            </label>
                            <input
                                v-model="option.value"
                                class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                                placeholder="Stored value"
                            />
                          </div>

                          <div>
                            <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                              <span>Score Delta</span>
                              <FieldHelpPopover field="scoreDelta" />
                            </label>
                            <input
                                v-model="option.score_delta"
                                type="number"
                                class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                                placeholder="0"
                            />
                          </div>

                          <div>
                            <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                              <span>Next Question</span>
                              <FieldHelpPopover field="nextQuestion" align="right" />
                            </label>
                            <select
                                v-model="option.next_step_step_key"
                                class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                            >
                              <option value="">Continue normally</option>
                              <option
                                  v-for="target in availableStepTargets(currentStep._tmpKey)"
                                  :key="target._tmpKey"
                                  :value="target.step_key || ''"
                              >
                                {{ target.step_order || '—' }} — {{ target.title || target.prompt_text || target.step_key || 'Untitled' }}
                              </option>
                            </select>
                          </div>

                          <div>
                            <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                              <span>Recommended Status</span>
                              <FieldHelpPopover field="recommendedStatusOption" />
                            </label>
                            <input
                                v-model="option.recommended_status"
                                class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                                placeholder="Optional"
                            />
                          </div>

                          <div>
                            <label class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                              <span>Recommended Stage Order</span>
                              <FieldHelpPopover field="recommendedStageOrderOption" />
                            </label>
                            <input
                                v-model="option.recommended_stage_order"
                                type="number"
                                min="1"
                                class="h-9 w-full rounded-lg border border-slate-300 px-2.5 text-xs"
                                placeholder="Optional"
                            />
                          </div>

                          <div class="flex items-end">
                            <div class="flex items-center gap-1.5">
                              <label class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                                <input v-model="option.requires_note" type="checkbox" />
                                <span>Requires Note</span>
                              </label>
                              <FieldHelpPopover field="requiresNote" />
                            </div>
                          </div>

                          <div class="flex items-end">
                            <div class="flex items-center gap-1.5">
                              <label class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                                <input v-model="option.is_disqualifying" type="checkbox" />
                                <span>Disqualify</span>
                              </label>
                              <FieldHelpPopover field="disqualifyOption" align="right" />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="flex justify-end">
                    <button
                        type="button"
                        class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-50"
                        :disabled="builderSaving || builderLoading"
                        @click="saveBuilderNow"
                    >
                      {{ builderSaving ? 'Saving...' : 'Save' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div><!-- builder -->
    </div>

    <QualificationScriptModal
        v-if="open"
        :key="modalKey"
        :open="open"
        :saving="saving"
        :form="form"
        :error="modalErr"
        @close="closeModal"
        @save="saveRow"
    />
  </AdminLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import DataTable from 'datatables.net-vue3'
import DataTablesCore from 'datatables.net'
import Responsive from 'datatables.net-responsive'
import FixedHeader from 'datatables.net-fixedheader'

import AdminLayout from '../../layouts/AdminLayout.vue'
import QualificationScriptModal from '../../components/admin/QualificationScriptModal.vue'
import FieldHelpPopover from '../../components/admin/FieldHelpPopover.vue'
import {
  cloneQualificationScript,
  deleteQualificationScript,
  fetchQualificationScript,
  fetchQualificationScripts,
  saveQualificationScript,
  saveQualificationScriptBuilder,
} from '../../api/admin'

DataTable.use(DataTablesCore)
DataTable.use(Responsive)
DataTable.use(FixedHeader)

const q = ref('')
const statusFilter = ref('all')

const rows = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const builderLoading = ref(false)
const builderSaving = ref(false)

const err = ref('')
const modalErr = ref('')
const builderErr = ref('')
const successMessage = ref('')
const builderSuccessMessage = ref('')

const open = ref(false)
const modalKey = ref(0)
const tableWrap = ref(null)

const mobilePage = ref(1)
const mobilePageSize = ref(10)

const selectedScriptId = ref(null)
const builderOpen = ref(false)
const activeStepIndex = ref(0)
const stepTabsPage = ref(1)
const stepTabsPageSize = 8

const form = reactive({
  id: null,
  name: '',
  slug: '',
  description: '',
  applies_to_ad_name: '',
  applies_to_platform: '',
  is_active: true,
  is_default: false,
  priority: 0,
  version: 1,
})

const builder = reactive({
  id: null,
  steps: [],
})

function createTempKey(prefix = 'tmp') {
  return `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`
}

function resetForm() {
  Object.assign(form, {
    id: null,
    name: '',
    slug: '',
    description: '',
    applies_to_ad_name: '',
    applies_to_platform: '',
    is_active: true,
    is_default: false,
    priority: 0,
    version: 1,
  })
}

function clearMessages() {
  err.value = ''
  modalErr.value = ''
  builderErr.value = ''
  successMessage.value = ''
  builderSuccessMessage.value = ''
}

function resetBuilderState() {
  selectedScriptId.value = null
  builderOpen.value = false
  activeStepIndex.value = 0
  stepTabsPage.value = 1
  builder.id = null
  builder.steps = []
}

function extractErrorMessage(e) {
  const errors = e?.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const firstKey = Object.keys(errors)[0]
    const firstValue = firstKey ? errors[firstKey] : null
    if (Array.isArray(firstValue) && firstValue.length) {
      return String(firstValue[0])
    }
  }

  return e?.response?.data?.message || e?.message || 'Request failed'
}

function asInt(value, fallback = null) {
  if (value === '' || value === null || value === undefined) return fallback
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : fallback
}

function asString(value) {
  return value == null ? '' : String(value)
}

function nullableString(value) {
  const result = asString(value).trim()
  return result === '' ? null : result
}

function normalizeOption(option = {}) {
  return {
    id: option.id ?? null,
    _tmpKey: createTempKey('option'),
    label: asString(option.label),
    value: asString(option.value),
    score_delta: option.score_delta ?? '',
    requires_note: Boolean(option.requires_note),
    is_disqualifying: Boolean(option.is_disqualifying),
    recommended_status: asString(option.recommended_status),
    recommended_stage_order: option.recommended_stage_order ?? '',
    next_step_id: option.next_step_id ?? null,
    next_step_step_key: asString(option.next_step_step_key),
  }
}

function createEmptyOption() {
  return normalizeOption({})
}

function normalizeStep(step = {}, index = 0) {
  const options = Array.isArray(step.options) ? step.options.map(normalizeOption) : []

  return {
    id: step.id ?? null,
    _tmpKey: createTempKey('step'),
    step_key: asString(step.step_key),
    title: asString(step.title),
    prompt_text: asString(step.prompt_text),
    help_text: asString(step.help_text),
    step_type: asString(step.step_type || 'single_choice'),
    step_order: step.step_order ?? index + 1,
    is_required: step.is_required !== false,
    is_terminal: Boolean(step.is_terminal),
    is_disqualifying: Boolean(step.is_disqualifying),
    recommended_status: asString(step.recommended_status),
    recommended_stage_order: step.recommended_stage_order ?? '',
    options: options.length ? options : [],
  }
}

function createEmptyStep(order = 1) {
  return {
    id: null,
    _tmpKey: createTempKey('step'),
    step_key: '',
    title: '',
    prompt_text: '',
    help_text: '',
    step_type: 'single_choice',
    step_order: order,
    is_required: true,
    is_terminal: false,
    is_disqualifying: false,
    recommended_status: '',
    recommended_stage_order: '',
    options: [createEmptyOption()],
  }
}

function normalizeScriptRow(row = {}) {
  return {
    id: row.id ?? null,
    name: asString(row.name),
    slug: asString(row.slug),
    description: asString(row.description),
    applies_to_ad_name: asString(row.applies_to_ad_name),
    applies_to_platform: asString(row.applies_to_platform),
    is_active: Boolean(row.is_active),
    is_default: Boolean(row.is_default),
    priority: row.priority ?? 0,
    version: row.version ?? 1,
    steps_count: row.steps_count ?? (Array.isArray(row.steps) ? row.steps.length : 0),
  }
}

function normalizeScriptDetail(payload = {}) {
  return {
    ...normalizeScriptRow(payload),
    steps: Array.isArray(payload.steps) ? payload.steps.map((step, index) => normalizeStep(step, index)) : [],
  }
}

const filteredRows = computed(() => {
  const needle = q.value.trim().toLowerCase()

  return rows.value.filter((row) => {
    if (statusFilter.value === 'active' && !row.is_active) return false
    if (statusFilter.value === 'inactive' && row.is_active) return false

    if (!needle) return true

    const haystack = [
      row.name,
      row.slug,
      row.description,
      row.applies_to_ad_name,
      row.applies_to_platform,
    ]
        .map((value) => asString(value).toLowerCase())
        .join(' ')

    return haystack.includes(needle)
  })
})

const mobileTotalPages = computed(() => {
  const total = Math.ceil(filteredRows.value.length / mobilePageSize.value)
  return total > 0 ? total : 1
})

const mobileRows = computed(() => {
  const start = (mobilePage.value - 1) * mobilePageSize.value
  const end = start + mobilePageSize.value
  return filteredRows.value.slice(start, end)
})

const selectedScriptSummary = computed(() => {
  return rows.value.find((row) => Number(row.id) === Number(selectedScriptId.value)) || null
})

const currentStep = computed(() => {
  return builder.steps[activeStepIndex.value] || null
})

const stepTabsTotalPages = computed(() => {
  const total = Math.ceil(builder.steps.length / stepTabsPageSize)
  return total > 0 ? total : 1
})

const pagedStepTabs = computed(() => {
  const start = (stepTabsPage.value - 1) * stepTabsPageSize
  const end = start + stepTabsPageSize

  return builder.steps.slice(start, end).map((step, offset) => ({
    step,
    index: start + offset,
  }))
})

watch([q, statusFilter], () => {
  mobilePage.value = 1
})

watch(
    () => builder.steps.length,
    (length) => {
      if (!length) {
        activeStepIndex.value = 0
        stepTabsPage.value = 1
        return
      }

      if (activeStepIndex.value >= length) {
        activeStepIndex.value = length - 1
      }

      if (activeStepIndex.value < 0) {
        activeStepIndex.value = 0
      }

      if (stepTabsPage.value > stepTabsTotalPages.value) {
        stepTabsPage.value = stepTabsTotalPages.value
      }
    }
)

function goToMobilePage(page) {
  if (page < 1) page = 1
  if (page > mobileTotalPages.value) page = mobileTotalPages.value
  mobilePage.value = page
}

function goToStepTabsPage(page) {
  if (page < 1) page = 1
  if (page > stepTabsTotalPages.value) page = stepTabsTotalPages.value
  stepTabsPage.value = page
}

function syncStepTabsPageToActive() {
  if (!builder.steps.length) {
    stepTabsPage.value = 1
    return
  }

  stepTabsPage.value = Math.floor(activeStepIndex.value / stepTabsPageSize) + 1
}

function esc(value) {
  return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;')
}

function displayValue(value) {
  const result = asString(value).trim()
  return result === '' ? '—' : result
}

function sortValue(value) {
  return asString(value).trim().toLowerCase()
}

function buildMatchSummary(row) {
  if (!row) return '—'

  const parts = []
  if (row.applies_to_platform) parts.push(`Platform: ${row.applies_to_platform}`)
  if (row.applies_to_ad_name) parts.push(`Ad: ${row.applies_to_ad_name}`)
  if (!parts.length && row.is_default) parts.push('Default fallback')
  return parts.length ? parts.join(' · ') : 'Manual only'
}

function badgeHtml(row) {
  const activeClass = row.is_active ? 'script-badge script-badge--on' : 'script-badge script-badge--off'
  const activeLabel = row.is_active ? 'Active' : 'Inactive'
  const defaultBadge = row.is_default ? '<span class="script-badge script-badge--default">Default</span>' : ''
  return `<div class="flex flex-wrap gap-1"><span class="${activeClass}">${activeLabel}</span>${defaultBadge}</div>`
}

const columns = [
  {
    title: 'Script',
    data: null,
    render: (_data, type, row) => {
      const value = displayValue(row.name)
      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return `
        <button type="button" class="qs-open-link" data-id="${row.id}">
          ${esc(value)}
        </button>
      `
    },
  },
  {
    title: 'Match',
    data: null,
    render: (_data, type, row) => {
      const value = buildMatchSummary(row)
      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
    },
  },
  {
    title: 'Version / Priority',
    data: null,
    render: (_data, type, row) => {
      const value = `v${row.version || 1} / ${row.priority ?? 0}`
      if (type === 'sort' || type === 'type' || type === 'filter') {
        return `${String(row.version || 1).padStart(4, '0')}_${String(row.priority ?? 0).padStart(4, '0')}`
      }

      return esc(value)
    },
  },
  {
    title: 'Questions',
    data: 'steps_count',
    render: (data, type) => {
      const value = asInt(data, 0)
      if (type === 'sort' || type === 'type' || type === 'filter') {
        return value
      }

      return esc(String(value))
    },
  },
  {
    title: 'Status',
    data: null,
    orderable: false,
    render: (_data, type, row) => {
      if (type === 'sort' || type === 'type' || type === 'filter') {
        return `${row.is_active ? '1' : '0'}_${row.is_default ? '1' : '0'}`
      }

      return badgeHtml(row)
    },
  },
]

const options = {
  paging: true,
  searching: false,
  info: true,
  pageLength: 25,
  lengthMenu: [10, 25, 50, 100],
  responsive: true,
  fixedHeader: true,
  autoWidth: false,
  order: [[0, 'asc']],
}

async function loadRows() {
  loading.value = true
  err.value = ''

  try {
    const response = await fetchQualificationScripts()
    const raw = Array.isArray(response?.data) ? response.data : Array.isArray(response) ? response : []
    rows.value = raw.map(normalizeScriptRow)

    if (selectedScriptId.value && !rows.value.some((row) => Number(row.id) === Number(selectedScriptId.value))) {
      resetBuilderState()
    }
  } catch (e) {
    err.value = extractErrorMessage(e)
    rows.value = []
  } finally {
    loading.value = false
  }
}

function buildScriptPayload() {
  return {
    name: nullableString(form.name),
    slug: nullableString(form.slug),
    description: nullableString(form.description),
    applies_to_ad_name: nullableString(form.applies_to_ad_name),
    applies_to_platform: nullableString(form.applies_to_platform),
    is_active: Boolean(form.is_active),
    is_default: Boolean(form.is_default),
    priority: asInt(form.priority, 0) ?? 0,
    version: asInt(form.version, 1) ?? 1,
  }
}

function openCreate() {
  clearMessages()
  resetForm()
  modalKey.value += 1
  open.value = true
}

function editRow(row) {
  if (!row) return

  clearMessages()

  Object.assign(form, {
    id: row.id ?? null,
    name: asString(row.name),
    slug: asString(row.slug),
    description: asString(row.description),
    applies_to_ad_name: asString(row.applies_to_ad_name),
    applies_to_platform: asString(row.applies_to_platform),
    is_active: Boolean(row.is_active),
    is_default: Boolean(row.is_default),
    priority: row.priority ?? 0,
    version: row.version ?? 1,
  })

  modalKey.value += 1
  open.value = true
}

function closeModal() {
  open.value = false
  modalErr.value = ''
}

function closeBuilder() {
  builderErr.value = ''
  builderSuccessMessage.value = ''
  resetBuilderState()
}

async function saveRow() {
  saving.value = true
  modalErr.value = ''
  err.value = ''
  successMessage.value = ''

  try {
    await saveQualificationScript(buildScriptPayload(), form.id || null)
    const savedId = form.id || null
    open.value = false
    resetForm()
    successMessage.value = 'Qualification script saved.'
    await loadRows()

    if (savedId && Number(selectedScriptId.value) === Number(savedId) && builderOpen.value) {
      await loadBuilder(savedId, { keepSuccess: true, openBuilder: true })
    }
  } catch (e) {
    modalErr.value = extractErrorMessage(e)
  } finally {
    saving.value = false
  }
}

async function deleteScriptById(id, target = 'builder') {
  deleting.value = true

  if (target === 'builder') {
    builderErr.value = ''
    builderSuccessMessage.value = ''
  } else {
    modalErr.value = ''
  }

  err.value = ''

  try {
    await deleteQualificationScript(id)

    if (Number(selectedScriptId.value) === Number(id)) {
      closeBuilder()
    }

    if (Number(form.id) === Number(id)) {
      open.value = false
      resetForm()
    }

    successMessage.value = 'Qualification script deleted.'
    await loadRows()
  } catch (e) {
    const message = extractErrorMessage(e)

    if (target === 'builder') {
      builderErr.value = message
    } else {
      modalErr.value = message
    }
  } finally {
    deleting.value = false
  }
}

async function deleteSelectedScriptFromBuilder() {
  const row = selectedScriptSummary.value
  if (!row?.id) return
  await deleteScriptById(row.id, 'builder')
}

async function cloneRow(row) {
  if (!row?.id) return

  clearMessages()

  try {
    await cloneQualificationScript(row.id)
    successMessage.value = 'Qualification script cloned.'
    await loadRows()
  } catch (e) {
    err.value = extractErrorMessage(e)
  }
}

function selectStep(index) {
  if (index < 0 || index >= builder.steps.length) return
  activeStepIndex.value = index
  syncStepTabsPageToActive()
}

async function openBuilderForRow(row) {
  if (!row?.id) return
  await loadBuilder(row.id, { openBuilder: true })
}

async function loadBuilder(id, options = {}) {
  builderLoading.value = true
  builderErr.value = ''
  if (!options.keepSuccess) {
    builderSuccessMessage.value = ''
  }

  try {
    const response = await fetchQualificationScript(id)
    const payload = response?.data ?? response
    const normalized = normalizeScriptDetail(payload)

    selectedScriptId.value = normalized.id
    builder.id = normalized.id
    builder.steps = normalized.steps.length ? normalized.steps : []
    activeStepIndex.value = 0
    stepTabsPage.value = 1
    builderOpen.value = Boolean(options.openBuilder)

    const rowIndex = rows.value.findIndex((row) => Number(row.id) === Number(normalized.id))
    if (rowIndex !== -1) {
      rows.value[rowIndex] = {
        ...rows.value[rowIndex],
        ...normalizeScriptRow(normalized),
        steps_count: normalized.steps.length,
      }
    }
  } catch (e) {
    builderErr.value = extractErrorMessage(e)
  } finally {
    builderLoading.value = false
  }
}

function addStep() {
  builderOpen.value = true
  builder.steps.push(createEmptyStep(builder.steps.length + 1))
  normalizeStepOrders()
  activeStepIndex.value = builder.steps.length - 1
  syncStepTabsPageToActive()
}

function removeStep(index) {
  builder.steps.splice(index, 1)
  normalizeStepOrders()

  if (builder.steps.length === 0) {
    activeStepIndex.value = 0
    stepTabsPage.value = 1
    return
  }

  if (activeStepIndex.value >= builder.steps.length) {
    activeStepIndex.value = builder.steps.length - 1
  }

  syncStepTabsPageToActive()
}

function moveStep(index, direction) {
  const target = index + direction
  if (target < 0 || target >= builder.steps.length) return

  const temp = builder.steps[index]
  builder.steps[index] = builder.steps[target]
  builder.steps[target] = temp
  normalizeStepOrders()
  activeStepIndex.value = target
  syncStepTabsPageToActive()
}

function normalizeStepOrders() {
  builder.steps.forEach((step, index) => {
    step.step_order = index + 1
  })
}

function addOption(step) {
  step.options.push(createEmptyOption())
}

function removeOption(step, optionIndex) {
  step.options.splice(optionIndex, 1)
}

function availableStepTargets(currentStepTmpKey) {
  return builder.steps.filter((step) => step._tmpKey !== currentStepTmpKey)
}

function buildBuilderPayload() {
  return {
    steps: builder.steps.map((step, index) => ({
      id: step.id ?? null,
      step_key: nullableString(step.step_key) || `step_${index + 1}`,
      title: nullableString(step.title),
      prompt_text: nullableString(step.prompt_text),
      help_text: nullableString(step.help_text),
      step_type: nullableString(step.step_type) || 'single_choice',
      step_order: asInt(step.step_order, index + 1) ?? index + 1,
      is_required: Boolean(step.is_required),
      is_terminal: Boolean(step.is_terminal),
      is_disqualifying: Boolean(step.is_disqualifying),
      recommended_status: nullableString(step.recommended_status),
      recommended_stage_order: asInt(step.recommended_stage_order, null),
      options: (Array.isArray(step.options) ? step.options : []).map((option) => {
        const nextStepTarget = builder.steps.find((item) => item.step_key === option.next_step_step_key)

        return {
          id: option.id ?? null,
          label: nullableString(option.label),
          value: nullableString(option.value),
          score_delta: asInt(option.score_delta, 0) ?? 0,
          requires_note: Boolean(option.requires_note),
          is_disqualifying: Boolean(option.is_disqualifying),
          recommended_status: nullableString(option.recommended_status),
          recommended_stage_order: asInt(option.recommended_stage_order, null),
          next_step_id: option.next_step_id ?? nextStepTarget?.id ?? null,
          next_step_step_key: nullableString(option.next_step_step_key),
        }
      }),
    })),
  }
}

async function saveBuilderNow() {
  if (!selectedScriptId.value) return

  builderSaving.value = true
  builderErr.value = ''
  builderSuccessMessage.value = ''
  err.value = ''

  try {
    await saveQualificationScriptBuilder(selectedScriptId.value, buildBuilderPayload())
    builderSuccessMessage.value = 'Script builder saved.'
    await loadBuilder(selectedScriptId.value, { keepSuccess: true, openBuilder: true })
    await loadRows()
  } catch (e) {
    builderErr.value = extractErrorMessage(e)
  } finally {
    builderSaving.value = false
  }
}

function handleTableClick(event) {
  const buildLink = event.target.closest('.qs-open-link')
  if (!buildLink) return

  event.preventDefault()
  const id = buildLink.getAttribute('data-id')
  const row = rows.value.find((item) => Number(item.id) === Number(id))
  if (row) {
    openBuilderForRow(row)
  }
}

onMounted(async () => {
  await loadRows()

  if (tableWrap.value) {
    tableWrap.value.addEventListener('click', handleTableClick)
  }
})

onBeforeUnmount(() => {
  if (tableWrap.value) {
    tableWrap.value.removeEventListener('click', handleTableClick)
  }
})
</script>

<style>
@import "datatables.net-dt";
@import "datatables.net-responsive-dt";
@import "datatables.net-fixedheader-dt";

.qualification-scripts-page .top-card {
  padding-top: 6px;
  padding-bottom: 6px;
}

.qualification-scripts-page .dt-container {
  padding: 0;
}

.qualification-scripts-page .dt-container .dt-layout-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin: 0;
  padding: 2px 8px;
}

.qualification-scripts-page .dt-container .dt-layout-row:first-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.qualification-scripts-page .dt-container .dt-layout-row:last-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.qualification-scripts-page .dt-container .dt-layout-cell {
  margin: 0;
}

.qualification-scripts-page .dt-search input,
.qualification-scripts-page .dt-length select {
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  padding: 6px 10px;
  font-size: 13px;
}

.qualification-scripts-page .dt-info,
.qualification-scripts-page .dt-paging {
  font-size: 13px;
  color: #64748b;
}

.qualification-scripts-page .dt-paging .dt-paging-button {
  border-radius: 10px !important;
  border: 1px solid #cbd5e1 !important;
  background: white !important;
  color: #334155 !important;
  padding: 3px 10px !important;
  margin-left: 4px !important;
}

.qualification-scripts-page .dt-paging .dt-paging-button.current {
  background: #0f172a !important;
  border-color: #0f172a !important;
  color: white !important;
}

.qualification-scripts-page .dt-scroll-head table.dataTable,
.qualification-scripts-page .dt-scroll-body table.dataTable,
.qualification-scripts-page table.dataTable {
  margin-top: 0 !important;
  margin-bottom: 0 !important;
}

.qualification-scripts-page table.dataTable thead th {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  color: #64748b !important;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0 !important;
}

.qualification-scripts-page table.dataTable tbody td {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  vertical-align: middle;
  line-height: 1.1rem;
}

.qualification-scripts-page table.dataTable tbody tr:hover {
  background: #f8fafc;
}

.qualification-scripts-page .qs-open-link {
  border: 0;
  background: transparent;
  padding: 0;
  color: #0369a1;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  text-align: left;
  white-space: nowrap;
}

.qualification-scripts-page .qs-open-link:hover {
  text-decoration: underline;
}

.qualification-scripts-page .script-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  border: 1px solid transparent;
  padding: 2px 8px;
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
}

.qualification-scripts-page .script-badge--on {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.qualification-scripts-page .script-badge--off {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

.qualification-scripts-page .script-badge--default {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

.qualification-scripts-page .script-badge--neutral {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #334155;
}

@media (max-width: 767px) {
  .qualification-scripts-page .dt-container .dt-layout-row {
    flex-direction: column;
    align-items: stretch;
  }

  .qualification-scripts-page .dt-container .dt-layout-cell {
    width: 100%;
  }

  .qualification-scripts-page .dt-length,
  .qualification-scripts-page .dt-search,
  .qualification-scripts-page .dt-info,
  .qualification-scripts-page .dt-paging {
    width: 100%;
    text-align: left !important;
  }
}
</style>