<?php

namespace App\Support;

/**
 * Allowed values for registration selects. Keep in sync with
 * lpo-core/src/lib/accountOptions.ts.
 */
class AccountOptions
{
    public const EXPERIENCE_LEVELS = ['entry', '1-3', '3-5', '5-10', '10+'];

    public const WORK_SETUPS = ['remote', 'hybrid', 'onsite'];

    /** Talent pipeline, in order. Only admins move a talent between stages. */
    public const TALENT_STATUSES = ['for_assessment', 'for_evaluation', 'active'];

    public const CV_MIMES = 'pdf,doc,docx';

    /** Kilobytes. */
    public const CV_MAX_SIZE = 5120;

    public const CANDIDATE_SKILLS = [
        'Lead Generation',
        'Prospect Research',
        'Cold Calling',
        'Cold Email',
        'Appointment Setting',
        'Virtual Assistance',
        'Data Entry',
        'CRM Management',
        'Executive Assistance',
        'Customer Support',
        'IT Support',
        'Bookkeeping',
    ];

    public const SERVICES = [
        'lead_generation',
        'prospect_research',
        'icp_qualification',
        'outreach_databases',
        'cold_outreach',
        'virtual_assistance',
        'it_service_desk',
        'flexible_support',
    ];

    public const COMPANY_SIZES = ['1-10', '11-50', '51-200', '201-500', '500+'];

    /** Client pipeline. Inactive is off the main flow (paused or ended engagements). */
    public const CLIENT_STATUSES = ['new_inquiry', 'discovery', 'proposal', 'active', 'inactive'];

    public const ENGAGEMENT_TYPES = ['dedicated', 'part_time', 'project_based'];

    public const LEAD_SOURCES = ['website', 'referral', 'outbound', 'event', 'partner', 'other'];

    public const CURRENCIES = ['USD', 'AUD', 'GBP', 'EUR', 'CAD', 'PHP'];

    public const INDUSTRIES = [
        'Technology',
        'SaaS',
        'Real Estate',
        'Professional Services',
        'Healthcare',
        'E-Commerce',
        'Financial Services',
        'Construction',
        'Travel & Hospitality',
        'Startups',
        'SMEs',
        'B2B Services',
        'Other',
    ];
}
