<template>
  <div class="inline-flex items-center align-middle">
    <button
        ref="triggerRef"
        type="button"
        class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full border border-slate-300 bg-white text-[9px] font-semibold leading-none text-slate-500 transition hover:border-slate-400 hover:text-slate-700 focus:border-slate-400 focus:text-slate-700 focus:outline-none"
        :aria-label="ariaLabel"
        @mouseenter="openPopover"
        @mouseleave="scheduleClose"
        @focus="openPopover"
        @blur="scheduleClose"
    >
      ?
    </button>

    <Teleport to="body">
      <div
          v-if="isOpen"
          ref="popoverRef"
          class="fixed z-[2500] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-slate-700 shadow-[0_8px_20px_rgba(15,23,42,0.12)]"
          :class="isStructured ? 'w-[250px]' : 'max-w-[260px]'"
          :style="popoverStyle"
          role="tooltip"
          @mouseenter="cancelClose"
          @mouseleave="scheduleClose"
      >
        <template v-if="isStructured">
          <div class="space-y-1 text-[10px] leading-[1.3]">
            <section>
              <div class="text-[9px] font-semibold uppercase tracking-[0.04em] text-slate-500">
                WHAT INFO DO WE PUT THERE
              </div>
              <div class="mt-0.5">
                {{ structuredContent.what }}
              </div>
            </section>

            <section>
              <div class="text-[9px] font-semibold uppercase tracking-[0.04em] text-slate-500">
                SAMPLE INFO NEEDED
              </div>
              <div class="mt-0.5">
                <template v-if="Array.isArray(structuredContent.example)">
                  <div
                      v-for="item in structuredContent.example"
                      :key="item"
                      class="mb-0.5 last:mb-0"
                  >
                    {{ item }}
                  </div>
                </template>
                <template v-else>
                  {{ structuredContent.example }}
                </template>
              </div>
            </section>

            <section>
              <div class="text-[9px] font-semibold uppercase tracking-[0.04em] text-slate-500">
                WHAT DOES THAT INFO DO
              </div>
              <div class="mt-0.5">
                {{ structuredContent.effect }}
              </div>
            </section>
          </div>
        </template>

        <template v-else>
          <div class="text-[10px] leading-[1.35] text-slate-700">
            {{ plainText }}
          </div>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'

const FIELD_HELP = {
  stepKey: {
    title: 'Step Key',
    what: 'A short internal code name for this question.',
    example: ['og_experience', 'frac_sand_experience', 'has_hopper_bottom_trailer'],
    effect: 'This gives the question a permanent internal ID so the system can track it correctly.',
  },
  title: {
    title: 'Title',
    what: 'A short internal title for this question in the builder.',
    example: ['OG Experience', 'Trailer Interchange', 'Earliest Start'],
    effect: 'This helps users quickly identify the question inside the script builder.',
  },
  order: {
    title: 'Order',
    what: 'The position number for this question in the script.',
    example: ['1', '2', '14'],
    effect: 'This controls where the question appears in the question sequence.',
  },
  promptText: {
    title: 'Prompt Text',
    what: 'The exact question the rep should see and ask.',
    example: ['Do you have frac sand hauling or oil and gas experience?'],
    effect: 'This is the main question shown to the rep during the call.',
  },
  inputType: {
    title: 'Input Type',
    what: 'The type of answer this question should collect.',
    example: ['Single Choice', 'Text', 'Yes / No'],
    effect: 'This controls how the rep answers the question in the script.',
  },
  helpText: {
    title: 'Help Text',
    what: 'Extra instructions for the rep about how to ask or explain the question.',
    example: ['Ask about hauling experience, not just general oilfield work.'],
    effect: 'This gives the rep guidance while asking the question.',
  },
  recommendedStatusStep: {
    title: 'Recommended Status',
    what: 'A status to use for this whole question.',
    example: ['qualified', 'contacted', 'disqualified', 'needs_follow_up'],
    effect: 'If the selected answer does not have its own status, the system will use this status for the whole question.',
  },
  recommendedStageOrderStep: {
    title: 'Recommended Stage Order',
    what: 'A stage number to use for this whole question.',
    example: ['1 = early/contacted', '2 = qualified', '99 = disqualified'],
    effect: 'If the selected answer does not have its own stage, the system will use this stage for the whole question.',
  },
  required: {
    title: 'Required',
    what: 'A yes or no setting for whether this question must be answered.',
    example: ['Turn on for important questions like CDL, equipment type, insurance, or location.'],
    effect: 'This marks the question as required before moving forward.',
  },
  terminal: {
    title: 'Terminal',
    what: 'A yes or no setting for whether this question should end the script.',
    example: ['Turn on for a final decision question.', 'Leave it off for normal questions.'],
    effect: 'If this question is answered, the script stops here.',
  },
  disqualifyStep: {
    title: 'Disqualify',
    what: 'A yes/no checkbox for whether this whole question is a knockout question.',
    example: [
      'Checked for questions like “Do you have a CDL?” or “Do you have a disqualifying safety violation?” if any answer to that question should eliminate the lead.',
      'Unchecked if only certain answers should disqualify.',
    ],
    effect: 'It marks the answer as disqualifying at the step level. When the rep answers that question, the backend stores is_disqualifying = true on the answer. The outcome summary then treats the session as disqualified and defaults the recommendation to status disqualified and stage order 99.',
  },
  optionLabel: {
    title: 'Label',
    what: 'The answer choice the rep should see.',
    example: ['No', 'Yes - Oil and Gas only', 'Yes - Frac Sand'],
    effect: 'This is the answer text shown in the script.',
  },
  optionValue: {
    title: 'Value',
    what: 'The clean stored value for this answer choice.',
    example: ['no', 'oil_gas_only', 'frac_sand'],
    effect: 'This saves the answer in a consistent format for tracking and logic.',
  },
  scoreDelta: {
    title: 'Score Delta',
    what: 'A numeric score change for this answer.',
    example: ['-10', '0', '15'],
    effect: 'This raises or lowers the lead score when this answer is selected.',
  },
  nextQuestion: {
    title: 'Next Question',
    what: 'The next question this answer should go to.',
    example: ['After “Yes” -> go to frac_sand_details', 'After “No” -> go to other_experience'],
    effect: 'This controls branching so different answers can go to different next questions.',
  },
  recommendedStatusOption: {
    title: 'Recommended Status',
    what: 'A status for this specific answer choice.',
    example: ['qualified', 'needs_follow_up', 'disqualified'],
    effect: 'If this answer is selected, the system will use this status for the lead.',
  },
  recommendedStageOrderOption: {
    title: 'Recommended Stage Order',
    what: 'A stage number for this specific answer choice.',
    example: ['Strong fit -> 2', 'Needs follow-up -> 1', 'Knockout answer -> 99'],
    effect: 'If this answer is selected, the system will use this stage for the lead.',
  },
  requiresNote: {
    title: 'Requires Note',
    what: 'A yes or no setting for whether the rep must type a note for this answer.',
    example: ['Turn on for answers like Other, Needs review, or Partial experience.'],
    effect: 'The rep must enter extra details before saving this answer.',
  },
  disqualifyOption: {
    title: 'Disqualify',
    what: 'A yes or no checkbox for whether this exact answer should knock out the lead.',
    example: ['No CDL -> checked', 'No hopper trailer -> checked', 'Has required equipment -> unchecked'],
    effect: 'If this answer is selected, the lead is marked as disqualified.',
  },
}

const props = defineProps({
  field: {
    type: String,
    default: '',
  },
  text: {
    type: String,
    default: '',
  },
  align: {
    type: String,
    default: 'auto',
    validator: (value) => ['auto', 'left', 'right'].includes(value),
  },
})

const triggerRef = ref(null)
const popoverRef = ref(null)
const isOpen = ref(false)
const closeTimer = ref(null)

const popoverTop = ref(0)
const popoverLeft = ref(0)
const popoverOrigin = ref('top left')

const isStructured = computed(() => Boolean(props.field && FIELD_HELP[props.field]))

const structuredContent = computed(() => {
  return FIELD_HELP[props.field] || {
    title: 'Help',
    what: 'Enter the information expected for this field.',
    example: ['Use the format your team expects.'],
    effect: 'This helps users understand what belongs in this field.',
  }
})

const plainText = computed(() => {
  const value = String(props.text || '').trim()
  return value || 'Help information is not available for this field.'
})

const ariaLabel = computed(() => {
  return isStructured.value ? `${structuredContent.value.title} help` : 'Field help'
})

const popoverStyle = computed(() => ({
  top: `${popoverTop.value}px`,
  left: `${popoverLeft.value}px`,
  pointerEvents: 'auto',
  transformOrigin: popoverOrigin.value,
}))

function cancelClose() {
  if (closeTimer.value) {
    clearTimeout(closeTimer.value)
    closeTimer.value = null
  }
}

function scheduleClose() {
  cancelClose()
  closeTimer.value = setTimeout(() => {
    isOpen.value = false
  }, 120)
}

async function updatePopoverPosition() {
  if (!triggerRef.value || !isOpen.value) {
    return
  }

  await nextTick()

  const triggerRect = triggerRef.value.getBoundingClientRect()
  const popoverWidth = popoverRef.value?.offsetWidth || 260
  const popoverHeight = popoverRef.value?.offsetHeight || 120
  const gap = 8
  const viewportPadding = 8

  const fitsRight = triggerRect.left + popoverWidth <= window.innerWidth - viewportPadding
  const fitsLeft = triggerRect.right - popoverWidth >= viewportPadding

  let left = triggerRect.left

  if (props.align === 'right') {
    left = triggerRect.right - popoverWidth
  } else if (props.align === 'left') {
    left = triggerRect.left
  } else {
    left = (!fitsRight && fitsLeft) ? (triggerRect.right - popoverWidth) : triggerRect.left
  }

  left = Math.max(
      viewportPadding,
      Math.min(left, window.innerWidth - popoverWidth - viewportPadding)
  )

  const shouldShowAbove = triggerRect.bottom + gap + popoverHeight > window.innerHeight - viewportPadding

  const top = shouldShowAbove
      ? Math.max(viewportPadding, triggerRect.top - popoverHeight - gap)
      : triggerRect.bottom + gap

  popoverTop.value = top
  popoverLeft.value = left
  popoverOrigin.value = shouldShowAbove
      ? (left < triggerRect.left ? 'bottom right' : 'bottom left')
      : (left < triggerRect.left ? 'top right' : 'top left')
}

async function openPopover() {
  cancelClose()
  isOpen.value = true
  await updatePopoverPosition()
}

function handleViewportChange() {
  if (!isOpen.value) {
    return
  }

  updatePopoverPosition()
}

if (typeof window !== 'undefined') {
  window.addEventListener('resize', handleViewportChange)
  window.addEventListener('scroll', handleViewportChange, true)
}

onBeforeUnmount(() => {
  cancelClose()

  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', handleViewportChange)
    window.removeEventListener('scroll', handleViewportChange, true)
  }
})
</script>