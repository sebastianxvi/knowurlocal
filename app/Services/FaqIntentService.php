<?php

namespace App\Services;

class FaqIntentService
{
    /**
     * Detect the primary information intent of a question.
     *
     * This service is intentionally rule-based.
     *
     * Its responsibility is only to determine WHAT TYPE of
     * information the user is asking for.
     *
     * It does not identify agencies, services, documents,
     * permits, or other government-specific topics.
     */
    public function detect(string $question): string
    {
        /*
         * Normalize the question before checking patterns.
         *
         * Lowercasing makes matching case-insensitive.
         * Collapsing whitespace makes unusual spacing harmless.
         */
        $text = mb_strtolower(
            trim(
                preg_replace('/\s+/u', ' ', $question)
            ),
            'UTF-8'
        );


        /*
         * =====================================================
         * REQUIREMENTS
         * =====================================================
         *
         * Questions asking what documents, materials,
         * prerequisites, or specific items are needed.
         *
         * These patterns describe the INFORMATION REQUEST,
         * not the government service itself.
         */
        if ($this->matches($text, [

            /*
             * Direct requirement wording.
             */
            'requirement',
            'requirements',
            'required',
            'needed',

            /*
             * English question structures.
             */
            'what do i need',
            'what do i need to bring',
            'what should i bring',
            'what documents',
            'what document',
            'what papers',
            'what requirements',
            'is required',
            'are required',
            'do i need',
            'do i need to bring',

            /*
             * Filipino / Taglish structures.
             */
            'kailangan',
            'mga kailangan',
            'kailangan ba',
            'kailangan ko ba',
            'kailangan bang',
            'ano ang kailangan',
            'ano kailangan',
            'anong kailangan',
            'anong mga dokumento',
            'mga dokumento',
            'ano ang mga requirements',
            'ano mga requirements',
            'dalhin',

        ])) {
            return 'requirements';
        }


        /*
         * =====================================================
         * PROCEDURE
         * =====================================================
         *
         * Questions asking HOW to perform a process.
         *
         * Notice that broad phrases such as "apply for" and
         * "request for" are intentionally NOT used here.
         *
         * Those phrases describe an activity, but they do not
         * necessarily mean the user is asking for instructions.
         *
         * Example:
         *
         * "What documents are required to apply for X?"
         *
         * is a requirements question, not a procedure question.
         */
        if ($this->matches($text, [

            /*
             * Explicit English procedure structures.
             */
            'how do i',
            'how can i',
            'how do i apply',
            'how can i apply',
            'how to apply',
            'how do i request',
            'how can i request',
            'how to request',
            'how do i get',
            'how can i get',
            'how to get',
            'how do i obtain',
            'how can i obtain',
            'how to obtain',

            /*
             * Process / step wording.
             */
            'what are the steps',
            'steps to',
            'steps for',
            'application process',
            'request process',
            'what is the process',
            'what are the process',

            /*
             * Filipino / Taglish procedure structures.
             */
            'paano mag-apply',
            'paano mag apply',
            'paano kumuha',
            'paano makakuha',
            'paano mag-request',
            'paano mag request',
            'paano humingi',
            'paano magparehistro',
            'paano mag-register',
            'ano ang proseso',
            'anong proseso',
            'mga hakbang',
            'paano gawin',
            'paano ito gawin',

        ])) {
            return 'procedure';
        }


        /*
         * =====================================================
         * ELIGIBILITY
         * =====================================================
         *
         * Questions asking who is qualified or allowed
         * to receive or use a service.
         */
        if ($this->matches($text, [

            'who can apply',
            'who is eligible',
            'am i eligible',
            'eligible',
            'eligibility',
            'qualified',
            'qualification',
            'who qualifies',

            'sino ang maaaring',
            'sino ang pwede',
            'sino ang puwede',
            'sino ang kwalipikado',
            'kwalipikado',
            'maaari ba akong',
            'pwede ba akong',
            'puwede ba akong',

        ])) {
            return 'eligibility';
        }


        /*
         * =====================================================
         * FEES
         * =====================================================
         *
         * Questions asking about cost, payment, or charges.
         */
        if ($this->matches($text, [

            'how much',
            'how much does',
            'how much is',
            'fee',
            'fees',
            'cost',
            'price',
            'charge',
            'charges',
            'payment',
            'pay',

            'magkano',
            'bayad',
            'bayaran',
            'may bayad',
            'magkano ang',

        ])) {
            return 'fees';
        }


        /*
         * =====================================================
         * PROCESSING TIME
         * =====================================================
         *
         * Questions asking how long a service takes or when
         * something will become available.
         */
        if ($this->matches($text, [

            'how long',
            'how long does it take',
            'processing time',
            'how many days',
            'when will it be ready',
            'when can i claim',
            'when can i get',

            'gaano katagal',
            'ilang araw',
            'kailan makukuha',
            'kailan ko makukuha',
            'oras ng processing',
            'tagal ng processing',

        ])) {
            return 'processing_time';
        }


        /*
         * =====================================================
         * OFFICE HOURS
         * =====================================================
         *
         * Questions about when an office opens, closes,
         * or normally operates.
         */
        if ($this->matches($text, [

            'office hours',
            'office hour',
            'opening hours',
            'closing hours',
            'what time does',
            'what time is',
            'when does the office open',
            'when does the office close',
            'open at',
            'close at',

            'anong oras',
            'anong oras bukas',
            'anong oras nagsasara',
            'oras ng opisina',
            'office schedule',

        ])) {
            return 'office_hours';
        }


        /*
         * =====================================================
         * CONTACT
         * =====================================================
         *
         * Questions asking how to contact an agency or office
         * through phone, email, or another contact channel.
         */
        if ($this->matches($text, [

            'contact number',
            'contact information',
            'contact info',
            'phone number',
            'telephone number',
            'mobile number',
            'email',
            'email address',
            'how can i contact',
            'how do i contact',
            'how to contact',

            'paano makontak',
            'paano kontakin',
            'contact number ng',
            'numero ng telepono',
            'telephone',

        ])) {
            return 'contact';
        }


        /*
         * =====================================================
         * LOCATION
         * =====================================================
         *
         * Questions asking where an agency or office is located.
         */
        if ($this->matches($text, [

            'where is the office',
            'where is the agency',
            'where can i find the office',
            'where can i find the agency',
            'office location',
            'agency location',
            'address',
            'office address',
            'where is',

            'saan ang opisina',
            'saan matatagpuan',
            'saan makikita',
            'lokasyon',
            'address ng',

        ])) {
            return 'location';
        }


        /*
         * =====================================================
         * SERVICE
         * =====================================================
         *
         * General questions asking what an agency offers,
         * provides, or supports.
         */
        if ($this->matches($text, [

            'what services',
            'what service',
            'services offered',
            'services do you offer',
            'what does the agency do',
            'what can i get',
            'what assistance',
            'what programs',

            'anong serbisyo',
            'anong mga serbisyo',
            'mga serbisyo',
            'serbisyo ng',
            'anong tulong',
            'anong mga programa',
            'mga programa',

        ])) {
            return 'service';
        }


        /*
         * =====================================================
         * OTHER
         * =====================================================
         *
         * "other" does NOT mean that the question is outside
         * KNOWURLOCAL's scope.
         *
         * It only means this rule-based classifier could not
         * confidently determine the information type.
         *
         * The FAQ matcher and optional semantic layer may still
         * be able to understand the question.
         */
        return 'other';
    }


    /**
     * Determine whether the normalized text contains
     * at least one recognized intent pattern.
     */
    private function matches(
        string $text,
        array $patterns
    ): bool {

        /*
         * Check every known pattern.
         */
        foreach ($patterns as $pattern) {

            /*
             * A pattern match is enough to classify the intent.
             */
            if (str_contains($text, $pattern)) {
                return true;
            }
        }


        /*
         * No recognized pattern was found.
         */
        return false;
    }
}