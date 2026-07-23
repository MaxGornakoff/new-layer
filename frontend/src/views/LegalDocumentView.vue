<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { applyLegalPlaceholders, legalPages } from '@/content/legal/meta'

import privacyPolicyHtml from '@/content/legal/privacy-policy.html?raw'
import personalDataConsentHtml from '@/content/legal/personal-data-consent.html?raw'
import publicOfferHtml from '@/content/legal/public-offer.html?raw'
import userAgreementHtml from '@/content/legal/user-agreement.html?raw'
import cookiePolicyHtml from '@/content/legal/cookie-policy.html?raw'

const bodies = {
  'privacy-policy': privacyPolicyHtml,
  'personal-data-consent': personalDataConsentHtml,
  'public-offer': publicOfferHtml,
  'user-agreement': userAgreementHtml,
  'cookie-policy': cookiePolicyHtml,
}

const route = useRoute()

const page = computed(() => {
  const slug = route.params.slug
  const meta = legalPages[slug]
  const body = bodies[slug]
  if (!meta || !body) return null

  return {
    title: meta.title,
    html: applyLegalPlaceholders(body),
  }
})
</script>

<template>
  <section class="legal-page mx-auto my-8 w-full max-w-[800px] sm:my-10 lg:my-12">
    <template v-if="page">
      <h1 class="m-0 text-2xl font-semibold uppercase leading-tight sm:text-[28px] lg:text-3xl">
        {{ page.title }}
      </h1>
      <article
        class="legal-content card mt-6 rounded-[20px] border-0 p-5 sm:p-7"
        v-html="page.html"
      />
    </template>
    <p v-else class="muted">Документ не найден.</p>
  </section>
</template>

<style scoped>
.legal-content :deep(h2) {
  margin: 1.75rem 0 0.75rem;
  font-size: 1.125rem;
  font-weight: 600;
  line-height: 1.35;
  color: #222222;
}

.legal-content :deep(h2:first-child) {
  margin-top: 0;
}

.legal-content :deep(p),
.legal-content :deep(ul),
.legal-content :deep(ol) {
  margin: 0 0 0.9rem;
  font-size: 15px;
  line-height: 1.65;
  color: #475569;
}

.legal-content :deep(ul),
.legal-content :deep(ol) {
  padding-left: 1.25rem;
}

.legal-content :deep(li + li) {
  margin-top: 0.35rem;
}

.legal-content :deep(a) {
  color: #222222;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.legal-content :deep(a:hover) {
  opacity: 0.7;
}

.legal-content :deep(em) {
  color: #64748b;
}

@media (min-width: 640px) {
  .legal-content :deep(p),
  .legal-content :deep(ul),
  .legal-content :deep(ol) {
    font-size: 1rem;
  }

  .legal-content :deep(h2) {
    font-size: 1.25rem;
  }
}
</style>
