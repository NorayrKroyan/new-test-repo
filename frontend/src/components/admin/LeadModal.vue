<template>
  <BaseAdminModal
      :open="open"
      :title="form.id ? 'Edit Lead' : 'New Lead'"
      :loading="loading"
      :saving="saving"
      :deleting="deleting"
      :record-id="form.id"
      size-memory-key="lead-modal"
      save-label="Save"
      @close="$emit('close')"
      @save="$emit('save')"
      @delete="$emit('delete')"
  >
    <div class="space-y-3">
      <div
          v-if="form.duplicate_of_lead_id"
          class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
      >
        This lead is marked as a duplicate of lead #{{ form.duplicate_of_lead_id }}
        <span v-if="form.duplicate_basis">({{ duplicateBasisLabel }})</span>.
      </div>

      <div
          ref="splitPaneRef"
          :class="[
            'grid grid-cols-1 gap-3',
            isEditMode ? 'xl:items-stretch xl:gap-0' : '',
          ]"
          :style="editPaneGridStyle"
      >
        <div
            :class="[
              'rounded-lg border border-gray-200 bg-white p-3',
              isEditMode ? 'xl:rounded-r-none xl:border-r-0' : '',
            ]"
        >
          <div class="mb-2 flex items-center justify-between gap-2">
            <div class="text-xs font-semibold text-gray-900">Lead Info</div>

            <div v-if="isEditMode" class="flex items-center gap-2">
              <button
                  type="button"
                  class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                  :disabled="contractSending || syncSaving || saving || deleting"
                  @click="$emit('send-contract')"
              >
                {{ contractSending ? 'Sending...' : 'Send Contract' }}
              </button>

              <button
                  type="button"
                  class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                  :disabled="syncSaving || contractSending || saving || deleting"
                  @click="$emit('sync-contact')"
              >
                {{ syncSaving ? 'Syncing...' : 'Sync Contact' }}
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-y-1.5 md:grid-cols-2 md:gap-x-4">
            <ModalFieldRow label="Source Name:" class="md:col-span-1">
              <input v-model="form.source_name" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Ad Name:" class="md:col-span-1">
              <input v-model="form.ad_name" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Platform:" class="md:col-span-1">
              <input v-model="form.platform" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Lead Status:" class="md:col-span-1">
              <select v-model="form.lead_status" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
                <option value="disqualified">Disqualified</option>
                <option value="converted_to_carrier">Converted to Carrier</option>
                <option v-if="form.duplicate_of_lead_id" value="duplicate">Duplicate</option>
              </select>
            </ModalFieldRow>

            <ModalFieldRow label="Stage:" class="md:col-span-1">
              <select v-model="form.lead_stage_id" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                <option value="">—</option>
                <option v-for="stage in stages" :key="stage.id" :value="String(stage.id)">
                  {{ stage.stage_name }} / {{ stage.stage_group }} / {{ stage.stage_order }}
                </option>
              </select>
            </ModalFieldRow>

            <ModalFieldRow label="Full Name:" class="md:col-span-1">
              <input v-model="form.full_name" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Email:" class="md:col-span-1">
              <input v-model="form.email" class="w-full max-w-[360px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Phone:" class="md:col-span-1">
              <input v-model="form.phone" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Insurance:" class="md:col-span-1">
              <input v-model="form.insurance_answer" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="City:" class="md:col-span-1">
              <input v-model="form.city" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="State:" class="md:col-span-1">
              <input v-model="form.state" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Carrier Class:" class="md:col-span-1">
              <input v-model="form.carrier_class" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="USDOT:" class="md:col-span-1">
              <input v-model="form.usdot" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Truck Count:" class="md:col-span-1">
              <input v-model="form.truck_count" type="number" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Trailer Count:" class="md:col-span-1">
              <input v-model="form.trailer_count" type="number" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Start Date Choice:" class="md:col-span-1">
              <input v-model="form.lead_date_choice" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow v-if="form.duplicate_of_lead_id" label="Duplicate Of Lead ID:" class="md:col-span-1">
              <input :value="form.duplicate_of_lead_id" disabled class="w-full max-w-[140px] rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs text-gray-600" />
            </ModalFieldRow>

            <ModalFieldRow label="Notes:" class="md:col-span-2">
              <textarea
                  v-model="form.notes"
                  rows="5"
                  class="min-h-[120px] max-h-[420px] w-full resize-y overflow-auto rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
              />
            </ModalFieldRow>

            <div v-if="isEditMode" class="md:col-span-2">
              <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2">
                  <div class="text-xs font-semibold text-slate-900">Call History</div>
                  <div class="text-[11px] text-slate-500">
                    {{ callHistoryLoading ? 'Loading...' : `${visibleCallHistory.length} record${visibleCallHistory.length === 1 ? '' : 's'}` }}
                  </div>
                </div>

                <div v-if="callHistoryLoading" class="px-3 py-3 text-xs text-slate-600">
                  Loading call history...
                </div>

                <div v-else-if="callHistoryError" class="px-3 py-3 text-xs text-rose-700">
                  {{ callHistoryError }}
                </div>

                <div v-else-if="callHistorySyncError && !visibleCallHistory.length" class="px-3 py-3 text-xs text-amber-700">
                  {{ callHistorySyncError }}
                </div>

                <div v-else-if="!visibleCallHistory.length" class="px-3 py-3 text-xs text-slate-600">
                  No call history for this lead.
                </div>

                <div v-else class="overflow-hidden">
                  <table class="w-full table-fixed border-collapse text-left text-[10px] leading-[13px] text-slate-700">
                    <thead class="bg-slate-50 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr class="h-[23px]">
                      <th class="w-[126px] border-b border-slate-200 px-2">Started</th>
                      <th class="w-[40px] border-b border-slate-200 px-2">Dir</th>
                      <th class="w-[72px] border-b border-slate-200 px-2">Status</th>
                      <th class="w-[44px] border-b border-slate-200 px-2">Dur</th>
                      <th class="w-[96px] border-b border-slate-200 px-2">Agent</th>
                      <th class="w-[170px] border-b border-slate-200 px-2">Numbers</th>
                      <th class="border-b border-slate-200 px-2">Note</th>
                    </tr>
                    </thead>

                    <tbody class="bg-white">
                    <tr
                        v-for="item in visibleCallHistory"
                        :key="item.id"
                        class="h-[23px] border-b border-slate-100 hover:bg-slate-50"
                    >
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="formatCallHistoryStarted(item.started_at)">
                        {{ formatCallHistoryStarted(item.started_at) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate">
                        {{ compactDirectionLabel(item.direction) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap">
                        <span
                            class="inline-flex items-center rounded-full border px-1.5 py-0 text-[10px] leading-4"
                            :class="callStatusBadgeClass(item.call_status)"
                        >
                          {{ compactStatusLabel(item.call_status) }}
                        </span>
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate">
                        {{ compactDuration(item.duration_seconds) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="item.agent_name || '—'">
                        {{ item.agent_name || '—' }}
                      </td>
                      <td
                          class="px-2 py-0 whitespace-nowrap truncate"
                          :title="`${item.from_number || '—'} → ${item.to_number || '—'}`"
                      >
                        {{ item.from_number || '—' }} → {{ item.to_number || '—' }}
                      </td>
                      <td class="px-2 py-0 truncate" :title="item.note || '—'">
                        {{ item.note || '—' }}
                      </td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div v-if="isEditMode" class="md:col-span-2">
              <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2">
                  <div class="text-xs font-semibold text-slate-900">SMS Log History</div>
                  <div class="text-[11px] text-slate-500">
                    {{ smsHistoryLoading ? 'Loading...' : `${visibleSmsHistory.length} record${visibleSmsHistory.length === 1 ? '' : 's'}` }}
                  </div>
                </div>

                <div v-if="smsHistoryLoading" class="px-3 py-3 text-xs text-slate-600">
                  Loading SMS history...
                </div>

                <div v-else-if="smsHistoryError" class="px-3 py-3 text-xs text-rose-700">
                  {{ smsHistoryError }}
                </div>

                <div v-else-if="!visibleSmsHistory.length" class="px-3 py-3 text-xs text-slate-600">
                  No SMS history for this lead.
                </div>

                <div v-else class="overflow-hidden">
                  <table class="w-full table-fixed border-collapse text-left text-[10px] leading-[13px] text-slate-700">
                    <thead class="bg-slate-50 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr class="h-[23px]">
                      <th class="w-[126px] border-b border-slate-200 px-2">Started</th>
                      <th class="w-[40px] border-b border-slate-200 px-2">Dir</th>
                      <th class="w-[72px] border-b border-slate-200 px-2">Status</th>
                      <th class="w-[120px] border-b border-slate-200 px-2">Contact</th>
                      <th class="w-[160px] border-b border-slate-200 px-2">Numbers</th>
                      <th class="border-b border-slate-200 px-2">Text</th>
                    </tr>
                    </thead>

                    <tbody class="bg-white">
                    <tr
                        v-for="item in visibleSmsHistory"
                        :key="item.id"
                        class="h-[23px] border-b border-slate-100 hover:bg-slate-50"
                    >
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="formatSmsHistoryStarted(item.message_created_at)">
                        {{ formatSmsHistoryStarted(item.message_created_at) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate">
                        {{ compactSmsDirectionLabel(item.direction) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap">
                        <span
                            class="inline-flex items-center rounded-full border px-1.5 py-0 text-[10px] leading-4"
                            :class="smsStatusBadgeClass(item.message_status, item.message_delivery_result)"
                        >
                          {{ compactSmsStatusLabel(item.message_status, item.message_delivery_result) }}
                        </span>
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="smsContactTitle(item)">
                        {{ smsContactLabel(item) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="smsNumbersTitle(item)">
                        {{ smsNumbersLabel(item) }}
                      </td>
                      <td class="px-2 py-0 truncate" :title="smsTextTitle(item)">
                        {{ smsTextLabel(item) }}
                      </td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div v-if="isEditMode" class="md:col-span-2">
              <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2">
                  <div class="text-xs font-semibold text-slate-900">Contract History</div>
                  <div class="text-[11px] text-slate-500">
                    {{ contractHistoryLoading ? 'Loading...' : `${visibleContractHistory.length} record${visibleContractHistory.length === 1 ? '' : 's'}` }}
                  </div>
                </div>

                <div v-if="contractHistoryLoading" class="px-3 py-3 text-xs text-slate-600">
                  Loading contract history...
                </div>

                <div v-else-if="contractHistoryError" class="px-3 py-3 text-xs text-rose-700">
                  {{ contractHistoryError }}
                </div>

                <div v-else-if="!visibleContractHistory.length" class="px-3 py-3 text-xs text-slate-600">
                  No contract history for this lead.
                </div>

                <div v-else class="overflow-hidden">
                  <table class="w-full table-fixed border-collapse text-left text-[10px] leading-[13px] text-slate-700">
                    <thead class="bg-slate-50 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr class="h-[23px]">
                      <th class="w-[126px] border-b border-slate-200 px-2">Started</th>
                      <th class="w-[150px] border-b border-slate-200 px-2">Source</th>
                      <th class="w-[86px] border-b border-slate-200 px-2">Status</th>
                      <th class="w-[170px] border-b border-slate-200 px-2">Recipient</th>
                      <th class="w-[170px] border-b border-slate-200 px-2">Document</th>
                      <th class="border-b border-slate-200 px-2">BoldSign ID</th>
                    </tr>
                    </thead>

                    <tbody class="bg-white">
                    <tr
                        v-for="item in visibleContractHistory"
                        :key="item.id"
                        class="h-[23px] border-b border-slate-100 hover:bg-slate-50"
                    >
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="formatContractHistoryStarted(item.sent_at || item.created_at)">
                        {{ formatContractHistoryStarted(item.sent_at || item.created_at) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="contractSourceTitle(item)">
                        {{ contractSourceLabel(item) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap">
                        <span
                            class="inline-flex items-center rounded-full border px-1.5 py-0 text-[10px] leading-4"
                            :class="contractStatusBadgeClass(item.status || item.boldsign_status)"
                        >
                          {{ compactContractStatusLabel(item.status || item.boldsign_status) }}
                        </span>
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="contractRecipientTitle(item)">
                        {{ contractRecipientLabel(item) }}
                      </td>
                      <td class="px-2 py-0 whitespace-nowrap truncate" :title="contractDocumentTitle(item)">
                        {{ contractDocumentLabel(item) }}
                      </td>
                      <td class="px-2 py-0 truncate" :title="contractBoldSignIdTitle(item)">
                        {{ contractBoldSignIdLabel(item) }}
                      </td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="isEditMode" class="relative hidden xl:flex items-stretch justify-center bg-slate-300/80">
          <div class="w-px bg-slate-300"></div>
          <button
              type="button"
              class="absolute inset-y-0 left-1/2 w-3 -translate-x-1/2 cursor-col-resize bg-transparent"
              title="Resize panels"
              @mousedown.prevent="startPaneResize"
          ></button>
        </div>

        <div v-if="isEditMode" class="rounded-lg border border-slate-500 bg-slate-200 p-3 xl:rounded-l-none xl:border-l-0">
          <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div class="text-xs font-semibold text-gray-900">Qualification</div>

            <div class="flex flex-col gap-2 sm:items-end">
              <span
                  v-if="qualificationSession?.recommended_status"
                  class="inline-flex rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700"
              >
                {{ formatStatus(qualificationSession.recommended_status) }}
              </span>

              <div class="w-full sm:w-[260px] space-y-2">
                <div>
                  <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    Script
                  </label>

                  <select
                      :value="selectedQualificationScriptId"
                      class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                      :disabled="qualificationScriptLoading"
                      @change="$emit('change-selected-qualification-script', $event.target.value)"
                  >
                    <option value="">Auto match</option>
                    <option
                        v-for="script in qualificationScripts"
                        :key="script.id"
                        :value="String(script.id)"
                    >
                      {{ script.name }}
                    </option>
                  </select>
                </div>

                <button
                    type="button"
                    class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                    :disabled="qualificationLoading || qualificationScriptLoading"
                    @click="$emit('qualify')"
                >
                  {{ qualificationSession ? 'Reload Qualification' : 'Load Qualification' }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="qualificationLoading" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600">
            Loading qualification...
          </div>

          <div v-else-if="qualificationError" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-2 text-xs text-rose-700">
            {{ qualificationError }}
          </div>

          <div v-else-if="!qualificationSession" class="space-y-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600">
              Qualification session is not loaded.
            </div>

            <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                @click="$emit('qualify')"
            >
              Load Qualification
            </button>
          </div>

          <div v-else class="space-y-3">
            <div
                v-if="qualificationSession?.script?.name"
                class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-700"
            >
              <span class="font-semibold">Current script:</span>
              {{ qualificationSession.script.name }}
            </div>

            <div
                v-if="hasRecommendation"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-2 text-xs text-emerald-800"
            >
              <div v-if="recommendedStageLabel">
                <span class="font-semibold">Recommended stage:</span>
                {{ recommendedStageLabel }}
              </div>

              <div v-if="qualificationSession?.recommended_status">
                <span class="font-semibold">Recommended status:</span>
                {{ formatStatus(qualificationSession.recommended_status) }}
              </div>
            </div>

            <div
                v-if="hideQuestionnaire"
                class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600"
            >
              This lead already has a finalized qualification result. The questionnaire is hidden, and saved answers remain visible below.
            </div>

            <template v-else>
              <div
                  v-if="currentStep"
                  class="rounded-lg border border-slate-200 bg-white px-2.5 py-2.5"
              >
                <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                  Current Step
                </div>

                <div class="text-xs font-semibold text-slate-900">
                  {{ currentStepTitle }}
                </div>

                <div
                    v-if="currentStepHelpText"
                    class="mt-1 text-xs leading-4 text-slate-600"
                >
                  {{ currentStepHelpText }}
                </div>

                <div class="mt-2.5 space-y-2">
                  <div v-if="currentStepOptions.length">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      Option
                    </label>

                    <select
                        v-model="qualificationForm.option_id"
                        class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                    >
                      <option value="">Select option</option>
                      <option
                          v-for="option in currentStepOptions"
                          :key="option.id"
                          :value="String(option.id)"
                      >
                        {{ optionLabel(option) }}
                      </option>
                    </select>
                  </div>

                  <div v-if="showAnswerValueInput">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      Answer Value
                    </label>

                    <input
                        v-model="qualificationForm.answer_value"
                        :type="answerValueInputType"
                        class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                    />
                  </div>

                  <div v-if="showAnswerTextInput">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      Answer Text
                    </label>

                    <textarea
                        v-model="qualificationForm.answer_text"
                        rows="2"
                        class="w-full resize-none rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                    />
                  </div>

                  <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      Note
                    </label>

                    <textarea
                        v-model="qualificationForm.note"
                        rows="2"
                        class="w-full resize-none rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                    />
                  </div>
                </div>
              </div>

              <div
                  v-else
                  class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600"
              >
                No current qualification step available.
              </div>

              <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <button
                    v-if="currentStep"
                    type="button"
                    class="w-full whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-50 sm:w-auto"
                    :disabled="qualificationSaving || qualificationApplying || !qualificationForm.step_id"
                    @click="$emit('save-answer')"
                >
                  {{ qualificationSaving ? 'Saving...' : 'Save Progress' }}
                </button>

                <button
                    type="button"
                    class="w-full whitespace-nowrap rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 sm:w-auto"
                    :disabled="qualificationSaving || qualificationApplying || qualificationSession?.status === 'completed' || qualificationSession?.status === 'disqualified'"
                    @click="$emit('complete-qualification')"
                >
                  Finish Session
                </button>

                <button
                    type="button"
                    class="w-full whitespace-nowrap rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50 disabled:opacity-50 sm:w-auto"
                    :disabled="qualificationSaving || qualificationApplying || !hasRecommendation"
                    @click="$emit('apply-recommended-stage')"
                >
                  {{ qualificationApplying ? 'Applying...' : 'Apply to Lead' }}
                </button>
              </div>
            </template>

            <div v-if="displayedAnswers.length" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2.5">
              <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {{ displayedAnswersTitle }}
              </div>

              <div class="space-y-2">
                <div
                    v-for="answer in displayedAnswers"
                    :key="answer.id"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs"
                >
                  <div class="font-medium text-slate-900">
                    {{ answerStepLabel(answer) }}
                  </div>

                  <div v-if="answer.option?.option_label || answer.option?.label" class="text-slate-700">
                    Option: {{ answer.option?.option_label || answer.option?.label }}
                  </div>

                  <div v-if="answer.answer_value !== null && answer.answer_value !== ''" class="text-slate-700">
                    Value: {{ answer.answer_value }}
                  </div>

                  <div v-if="answer.answer_text" class="text-slate-700">
                    Text: {{ answer.answer_text }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import BaseAdminModal from './BaseAdminModal.vue'
import ModalFieldRow from './ModalFieldRow.vue'

const leadModalSplitMemory = new Map()
const LEAD_MODAL_SPLIT_STORAGE_KEY = 'lead-modal-split-weight'

const props = defineProps({
  open: Boolean,
  loading: { type: Boolean, default: false },
  saving: Boolean,
  deleting: { type: Boolean, default: false },
  syncSaving: {
    type: Boolean,
    default: false,
  },
  form: {
    type: Object,
    default: () => ({}),
  },
  stages: {
    type: Array,
    default: () => [],
  },
  qualificationLoading: {
    type: Boolean,
    default: false,
  },
  qualificationSaving: {
    type: Boolean,
    default: false,
  },
  qualificationApplying: {
    type: Boolean,
    default: false,
  },
  qualificationSession: {
    type: Object,
    default: null,
  },
  qualificationHistorySession: {
    type: Object,
    default: null,
  },
  qualificationForm: {
    type: Object,
    default: () => ({
      step_id: null,
      option_id: '',
      answer_value: '',
      answer_text: '',
      note: '',
    }),
  },
  qualificationError: {
    type: String,
    default: '',
  },
  qualificationScripts: {
    type: Array,
    default: () => [],
  },
  qualificationScriptLoading: {
    type: Boolean,
    default: false,
  },
  selectedQualificationScriptId: {
    type: [String, Number],
    default: '',
  },
  callHistory: {
    type: Array,
    default: () => [],
  },
  callHistoryLeadId: {
    type: [String, Number],
    default: null,
  },
  callHistoryLoading: {
    type: Boolean,
    default: false,
  },
  callHistoryError: {
    type: String,
    default: '',
  },
  callHistorySyncError: {
    type: String,
    default: '',
  },
  smsHistory: {
    type: Array,
    default: () => [],
  },
  smsHistoryLeadId: {
    type: [String, Number],
    default: null,
  },
  smsHistoryLoading: {
    type: Boolean,
    default: false,
  },
  smsHistoryError: {
    type: String,
    default: '',
  },
  contractSending: {
    type: Boolean,
    default: false,
  },
  contractHistory: {
    type: Array,
    default: () => [],
  },
  contractHistoryLeadId: {
    type: [String, Number],
    default: null,
  },
  contractHistoryLoading: {
    type: Boolean,
    default: false,
  },
  contractHistoryError: {
    type: String,
    default: '',
  },
})

const isEditMode = computed(() => Boolean(props.form?.id))
const splitPaneRef = ref(null)
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1440)
const splitLeftWeight = ref(50)
const paneResizeState = ref(null)

const XL_BREAKPOINT = 1280

const isXlDesktop = computed(() => viewportWidth.value >= XL_BREAKPOINT)

const editPaneGridStyle = computed(() => {
  if (!isEditMode.value || !isXlDesktop.value) {
    return null
  }

  const rightWeight = Math.max(1, 100 - splitLeftWeight.value)

  return {
    gridTemplateColumns: `minmax(0, ${splitLeftWeight.value}fr) 12px minmax(320px, ${rightWeight}fr)`,
  }
})
const visibleCallHistory = computed(() => {
  const currentLeadId = Number(props.form?.id || 0)
  const responseLeadId = Number(props.callHistoryLeadId || 0)

  if (!currentLeadId || !responseLeadId || currentLeadId !== responseLeadId) {
    return []
  }

  return (Array.isArray(props.callHistory) ? props.callHistory : []).filter((item) => {
    return Number(item?.lead_id || 0) === currentLeadId
  })
})

const visibleSmsHistory = computed(() => {
  const currentLeadId = Number(props.form?.id || 0)
  const responseLeadId = Number(props.smsHistoryLeadId || 0)

  if (!currentLeadId || !responseLeadId || currentLeadId !== responseLeadId) {
    return []
  }

  return (Array.isArray(props.smsHistory) ? props.smsHistory : []).filter((item) => {
    return Number(item?.lead_id || 0) === currentLeadId
  })
})

const visibleContractHistory = computed(() => {
  const currentLeadId = Number(props.form?.id || 0)
  const responseLeadId = Number(props.contractHistoryLeadId || 0)

  if (!currentLeadId || !responseLeadId || currentLeadId !== responseLeadId) {
    return []
  }

  return (Array.isArray(props.contractHistory) ? props.contractHistory : []).filter((item) => {
    return Number(item?.lead_id || 0) === currentLeadId
  })
})

const duplicateBasisLabel = computed(() => {
  const value = String(props.form?.duplicate_basis ?? '').trim().toLowerCase()

  if (value === 'phone') return 'matched by phone'
  if (value === 'email') return 'matched by email'
  if (value === 'manual') return 'marked manually'
  if (value === 'merged') return 'merged'

  return value || 'duplicate'
})

const normalizedLeadStatus = computed(() => {
  return String(props.form?.lead_status ?? '').trim().toLowerCase()
})

const hideQuestionnaire = computed(() => {
  return ['qualified', 'disqualified', 'converted_to_carrier'].includes(normalizedLeadStatus.value)
})

const currentStep = computed(() => props.qualificationSession?.current_step ?? null)

const currentStepOptions = computed(() => {
  return Array.isArray(currentStep.value?.options) ? currentStep.value.options : []
})

const currentStepInputType = computed(() => {
  return String(
      currentStep.value?.input_type ??
      currentStep.value?.answer_input_type ??
      ''
  ).trim().toLowerCase()
})

const currentStepTitle = computed(() => {
  return (
      currentStep.value?.question_text ||
      currentStep.value?.question ||
      currentStep.value?.title ||
      currentStep.value?.step_label ||
      currentStep.value?.label ||
      currentStep.value?.step_key ||
      'Qualification Step'
  )
})

const currentStepHelpText = computed(() => {
  return (
      currentStep.value?.help_text ||
      currentStep.value?.description ||
      currentStep.value?.instructions ||
      ''
  )
})

const showAnswerValueInput = computed(() => {
  if (!currentStep.value) return false
  if (currentStepOptions.value.length > 0) return false

  return ['number', 'numeric', 'integer', 'decimal', 'float', 'text', 'short_text', 'string', ''].includes(currentStepInputType.value)
})

const showAnswerTextInput = computed(() => {
  if (!currentStep.value) return false
  if (currentStepOptions.value.length > 0) return false

  return ['textarea', 'text_area', 'long_text', 'note', 'notes'].includes(currentStepInputType.value)
})

const answerValueInputType = computed(() => {
  return ['number', 'numeric', 'integer', 'decimal', 'float'].includes(currentStepInputType.value)
      ? 'number'
      : 'text'
})

const recommendedStageLabel = computed(() => {
  const stage = props.qualificationSession?.recommended_stage

  if (stage?.stage_name) {
    return stage.stage_group ? `${stage.stage_name} (${stage.stage_group})` : stage.stage_name
  }

  return props.qualificationSession?.recommended_stage_name || props.qualificationSession?.recommended_stage_label || ''
})

const hasRecommendation = computed(() => {
  return Boolean(recommendedStageLabel.value || props.qualificationSession?.recommended_status)
})

const sessionAnswers = computed(() => {
  return Array.isArray(props.qualificationSession?.answers) ? props.qualificationSession.answers : []
})

const historyAnswers = computed(() => {
  return Array.isArray(props.qualificationHistorySession?.answers) ? props.qualificationHistorySession.answers : []
})

const displayedAnswers = computed(() => {
  return sessionAnswers.value.length ? sessionAnswers.value : historyAnswers.value
})

const displayedAnswersTitle = computed(() => {
  return sessionAnswers.value.length ? 'Saved Answers' : 'Previous Answers'
})

function optionLabel(option) {
  return (
      option?.option_label ||
      option?.label ||
      option?.option_value ||
      option?.value ||
      `Option #${option?.id ?? ''}`
  )
}

function formatStatus(value) {
  const normalized = String(value ?? '').trim().toLowerCase()

  if (normalized.includes('disqual')) {
    return 'Disqualified'
  }

  if (normalized === 'ready_for_senior_rep') {
    return 'Qualified'
  }

  return String(value ?? '')
      .trim()
      .replaceAll('_', ' ')
      .replace(/\s+/g, ' ')
      .replace(/\b\w/g, (char) => char.toUpperCase())
}

function answerStepLabel(answer) {
  return (
      answer?.step?.question_text ||
      answer?.step?.question ||
      answer?.step?.title ||
      answer?.step?.step_label ||
      answer?.step?.label ||
      answer?.step?.step_key ||
      `Step #${answer?.step_id ?? ''}`
  )
}

function formatCallHistoryStarted(value) {
  if (!value) return '—'

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleString([], {
    year: '2-digit',
    month: 'numeric',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function compactDirectionLabel(value) {
  const normalized = String(value ?? '').trim().toLowerCase()

  if (normalized === 'outbound') return 'Out'
  if (normalized === 'inbound') return 'In'

  return normalized ? formatStatus(normalized) : '—'
}

function compactStatusLabel(value) {
  const normalized = String(value ?? '').trim().toLowerCase()

  if (normalized === 'completed') return 'Done'
  if (normalized === 'missed') return 'Missed'
  if (normalized === 'voicemail') return 'VM'
  if (normalized === 'failed') return 'Failed'
  if (normalized === 'busy') return 'Busy'
  if (normalized === 'no_answer') return 'No Ans'

  return normalized ? formatStatus(normalized) : '—'
}

function callStatusBadgeClass(value) {
  const normalized = String(value ?? '').trim().toLowerCase()

  if (normalized === 'completed') {
    return 'border-emerald-200 bg-emerald-50 text-emerald-700'
  }

  if (['missed', 'failed', 'busy', 'no_answer'].includes(normalized)) {
    return 'border-rose-200 bg-rose-50 text-rose-700'
  }

  if (normalized === 'voicemail') {
    return 'border-amber-200 bg-amber-50 text-amber-700'
  }

  return 'border-slate-200 bg-slate-50 text-slate-600'
}

function compactDuration(seconds) {
  const total = Number(seconds || 0)

  if (!Number.isFinite(total) || total <= 0) {
    return '0s'
  }

  const minutes = Math.floor(total / 60)
  const remainder = total % 60

  if (minutes <= 0) {
    return `${remainder}s`
  }

  return `${minutes}m ${String(remainder).padStart(2, '0')}s`
}

function formatSmsHistoryStarted(value) {
  return formatCallHistoryStarted(value)
}

function compactSmsDirectionLabel(value) {
  return compactDirectionLabel(value)
}

function compactSmsStatusLabel(messageStatus, deliveryResult) {
  const status = String(messageStatus ?? '').trim().toLowerCase()
  const delivery = String(deliveryResult ?? '').trim().toLowerCase()
  const normalized = status || delivery

  if (!normalized) return '—'
  if (normalized === 'delivered') return 'Delivered'
  if (normalized === 'received') return 'Received'
  if (normalized === 'sent') return 'Sent'
  if (normalized === 'queued') return 'Queued'
  if (normalized === 'sending') return 'Sending'
  if (normalized === 'failed') return 'Failed'
  if (normalized === 'undelivered') return 'Undeliv'

  return formatStatus(normalized)
}

function smsStatusBadgeClass(messageStatus, deliveryResult) {
  const normalized = String(messageStatus ?? deliveryResult ?? '').trim().toLowerCase()

  if (['delivered', 'received', 'sent'].includes(normalized)) {
    return 'border-emerald-200 bg-emerald-50 text-emerald-700'
  }

  if (['failed', 'undelivered'].includes(normalized)) {
    return 'border-rose-200 bg-rose-50 text-rose-700'
  }

  if (['queued', 'sending', 'pending'].includes(normalized)) {
    return 'border-amber-200 bg-amber-50 text-amber-700'
  }

  return 'border-slate-200 bg-slate-50 text-slate-600'
}

function parseSmsToNumbers(value) {
  if (Array.isArray(value)) {
    return value.filter(Boolean).map((item) => String(item))
  }

  if (typeof value !== 'string' || value.trim() === '') {
    return []
  }

  try {
    const decoded = JSON.parse(value)
    return Array.isArray(decoded) ? decoded.filter(Boolean).map((item) => String(item)) : []
  } catch (_error) {
    return []
  }
}

function smsContactLabel(item) {
  return item?.contact_name || item?.contact_phone || '—'
}

function smsContactTitle(item) {
  const name = item?.contact_name || '—'
  const phone = item?.contact_phone || '—'
  return `${name} | ${phone}`
}

function smsNumbersLabel(item) {
  const toNumbers = parseSmsToNumbers(item?.to_numbers_json)
  const toLabel = toNumbers.length ? toNumbers.join(', ') : (item?.target_phone || '—')
  return `${item?.from_number || '—'} → ${toLabel}`
}

function smsNumbersTitle(item) {
  const toNumbers = parseSmsToNumbers(item?.to_numbers_json)
  const targetPhone = item?.target_phone || '—'
  const toLabel = toNumbers.length ? toNumbers.join(', ') : targetPhone
  return `${item?.from_number || '—'} → ${toLabel}`
}

function smsTextLabel(item) {
  const text = String(item?.text ?? '').trim()
  if (text !== '') {
    return text
  }

  return item?.is_mms ? 'MMS' : '—'
}

function smsTextTitle(item) {
  return smsTextLabel(item)
}

function formatContractHistoryStarted(value) {
  return formatCallHistoryStarted(value)
}

function contractSourceLabel(item) {
  const sourceType = String(item?.source_type ?? '').trim().toLowerCase()

  if (sourceType === 'template') {
    return item?.template_name || 'Template'
  }

  if (sourceType === 'upload') {
    return item?.uploaded_original_name || 'Upload'
  }

  return sourceType ? formatStatus(sourceType) : '—'
}

function contractSourceTitle(item) {
  return contractSourceLabel(item)
}

function compactContractStatusLabel(value) {
  const normalized = String(value ?? '').trim().toLowerCase()

  if (!normalized) return '—'
  if (normalized === 'preparing') return 'Preparing'
  if (normalized === 'sent') return 'Sent'
  if (normalized === 'viewed') return 'Viewed'
  if (normalized === 'completed') return 'Completed'
  if (normalized === 'declined') return 'Declined'
  if (normalized === 'expired') return 'Expired'
  if (normalized === 'cancelled') return 'Cancelled'
  if (normalized === 'failed') return 'Failed'

  return formatStatus(normalized)
}

function contractStatusBadgeClass(value) {
  const normalized = String(value ?? '').trim().toLowerCase()

  if (['sent', 'viewed'].includes(normalized)) {
    return 'border-emerald-200 bg-emerald-50 text-emerald-700'
  }

  if (normalized === 'completed') {
    return 'border-sky-200 bg-sky-50 text-sky-700'
  }

  if (['declined', 'expired', 'cancelled', 'failed'].includes(normalized)) {
    return 'border-rose-200 bg-rose-50 text-rose-700'
  }

  if (normalized === 'preparing') {
    return 'border-amber-200 bg-amber-50 text-amber-700'
  }

  return 'border-slate-200 bg-slate-50 text-slate-600'
}

function contractRecipientLabel(item) {
  const name = String(item?.recipient_name ?? '').trim()
  const email = String(item?.recipient_email ?? '').trim()

  if (name && email) {
    return `${name} | ${email}`
  }

  return name || email || '—'
}

function contractRecipientTitle(item) {
  return contractRecipientLabel(item)
}

function contractDocumentLabel(item) {
  return (
      item?.document_name ||
      item?.template_name ||
      item?.uploaded_original_name ||
      '—'
  )
}

function contractDocumentTitle(item) {
  return contractDocumentLabel(item)
}

function contractBoldSignIdLabel(item) {
  return item?.boldsign_document_id || '—'
}

function contractBoldSignIdTitle(item) {
  return contractBoldSignIdLabel(item)
}

function clampSplitWeight(value) {
  const width = splitPaneRef.value?.getBoundingClientRect()?.width || Math.max(960, viewportWidth.value - 96)
  const safeWidth = Math.max(width, 1)
  const minWeight = Math.max(32, Math.ceil((320 / safeWidth) * 100))
  const maxWeight = 100 - minWeight

  return Math.min(Math.max(Number(value) || 50, minWeight), maxWeight)
}

function persistSplitWeight(value = splitLeftWeight.value) {
  const normalized = clampSplitWeight(value)

  splitLeftWeight.value = normalized
  leadModalSplitMemory.set(LEAD_MODAL_SPLIT_STORAGE_KEY, normalized)

  if (typeof window !== 'undefined') {
    window.localStorage.setItem(LEAD_MODAL_SPLIT_STORAGE_KEY, String(normalized))
  }
}

function restoreSplitWeight() {
  let remembered = leadModalSplitMemory.get(LEAD_MODAL_SPLIT_STORAGE_KEY)

  if ((remembered === undefined || remembered === null) && typeof window !== 'undefined') {
    const raw = window.localStorage.getItem(LEAD_MODAL_SPLIT_STORAGE_KEY)
    if (raw !== null && raw !== '') {
      remembered = Number(raw)
    }
  }

  if (remembered === undefined || remembered === null || !Number.isFinite(Number(remembered))) {
    return false
  }

  const normalized = clampSplitWeight(Number(remembered))
  splitLeftWeight.value = normalized
  leadModalSplitMemory.set(LEAD_MODAL_SPLIT_STORAGE_KEY, normalized)
  return true
}

function syncViewport() {
  if (typeof window === 'undefined') {
    return
  }

  viewportWidth.value = window.innerWidth
}

function startPaneResize(event) {
  if (!isEditMode.value || !isXlDesktop.value || !splitPaneRef.value) {
    return
  }

  const rect = splitPaneRef.value.getBoundingClientRect()

  paneResizeState.value = {
    left: rect.left,
    width: rect.width,
  }

  updatePaneSplit(event.clientX)

  window.addEventListener('mousemove', onPaneResizeMove)
  window.addEventListener('mouseup', stopPaneResize)
}

function onPaneResizeMove(event) {
  updatePaneSplit(event.clientX)
}

function updatePaneSplit(clientX) {
  if (!paneResizeState.value) {
    return
  }

  const width = Math.max(1, paneResizeState.value.width)
  const rawWeight = ((clientX - paneResizeState.value.left) / width) * 100

  splitLeftWeight.value = clampSplitWeight(rawWeight)
  persistSplitWeight(splitLeftWeight.value)
}

function stopPaneResize() {
  if (!paneResizeState.value) {
    return
  }

  paneResizeState.value = null
  window.removeEventListener('mousemove', onPaneResizeMove)
  window.removeEventListener('mouseup', stopPaneResize)
}

watch(
    () => props.open,
    async (value) => {
      if (!value) {
        return
      }

      await nextTick()
      restoreSplitWeight()
    }
)

onMounted(async () => {
  syncViewport()
  window.addEventListener('resize', syncViewport)

  await nextTick()
  restoreSplitWeight()
})

onBeforeUnmount(() => {
  persistSplitWeight()
  stopPaneResize()
  window.removeEventListener('resize', syncViewport)
})

defineEmits([
  'close',
  'save',
  'delete',
  'sync-contact',
  'send-contract',
  'qualify',
  'change-selected-qualification-script',
  'save-answer',
  'complete-qualification',
  'apply-recommended-stage',
])
</script>