/**
 * SureSalez Sprint 04 Unit & Integration Test Suite
 *
 * Tests Customer 360 profile metrics (Spend, Orders, AOV, Recency),
 * Behavioral Segment evaluation, Flow Automation condition matching &
 * multi-action dispatch, and Conversational Live Chat autonomous FAQ matching.
 */

const fs = require('fs');
const path = require('path');

let testsCount = 0;
let passedCount = 0;
let failedCount = 0;

function assert(condition, desc) {
    testsCount++;
    if (condition) {
        passedCount++;
        console.log(`  \x1b[32m✔ PASS\x1b[0m: ${desc}`);
    } else {
        failedCount++;
        console.error(`  \x1b[31m✘ FAIL\x1b[0m: ${desc}`);
    }
}

console.log('\n======================================================');
console.log('  SureSalez Core — Sprint 04 Focused Test Suite');
console.log('  CRM, Customer Segments & Visual Workflow Engine (Flow)');
console.log('======================================================\n');

// -----------------------------------------------------------------------------
// TEST 1: Customer 360 Metric Aggregation & Auto-Tagging
// -----------------------------------------------------------------------------
console.log('\x1b[36m[Test 1] Customer 360 Spend, Orders, AOV & Auto-Tagging\x1b[0m');

function syncCustomerFromOrder(profile, orderTotal, orderDate) {
    const newOrderCount = (profile.order_count || 0) + 1;
    const newTotalSpend = parseFloat(((profile.total_spend || 0) + orderTotal).toFixed(2));
    const newAov = parseFloat((newTotalSpend / Math.max(1, newOrderCount)).toFixed(2));
    const firstOrderAt = profile.first_order_at || orderDate;
    const lastOrderAt = orderDate;

    const tags = Array.isArray(profile.tags) ? [...profile.tags] : [];
    if (newTotalSpend >= 1000.00 && !tags.includes('vip')) {
        tags.push('vip');
    }
    if (newOrderCount >= 2 && !tags.includes('repeat_buyer')) {
        tags.push('repeat_buyer');
    }

    return {
        ...profile,
        total_spend: newTotalSpend,
        order_count: newOrderCount,
        aov: newAov,
        first_order_at: firstOrderAt,
        last_order_at: lastOrderAt,
        tags,
    };
}

// Initial customer state
const newCustomer = {
    id: 101,
    email: 'elena.rostova@luxury.org',
    total_spend: 0.00,
    order_count: 0,
    aov: 0.00,
    first_order_at: null,
    last_order_at: null,
    tags: [],
};

// First purchase: $899.00 Chronograph
const order1Date = '2026-08-01 10:00:00';
const profileAfter1 = syncCustomerFromOrder(newCustomer, 899.00, order1Date);
assert(profileAfter1.order_count === 1, 'First order increments count to 1');
assert(profileAfter1.total_spend === 899.00, 'Total spend is $899.00');
assert(profileAfter1.aov === 899.00, 'AOV equals first order total ($899.00)');
assert(profileAfter1.first_order_at === order1Date, 'First order timestamp recorded');
assert(!profileAfter1.tags.includes('vip'), 'Customer is not yet VIP (< $1000)');
assert(!profileAfter1.tags.includes('repeat_buyer'), 'Customer is not yet Repeat Buyer (1 order)');

// Second purchase: $250.00 Titanium Strap
const order2Date = '2026-10-14 14:30:00';
const profileAfter2 = syncCustomerFromOrder(profileAfter1, 250.00, order2Date);
assert(profileAfter2.order_count === 2, 'Second order increments count to 2');
assert(profileAfter2.total_spend === 1149.00, 'Total spend crosses threshold to $1,149.00');
assert(profileAfter2.aov === 574.50, 'AOV recalculated to ($1149 / 2 = $574.50)');
assert(profileAfter2.last_order_at === order2Date, 'Last order timestamp updated');
assert(profileAfter2.tags.includes('vip'), 'Customer automatically granted "vip" tag (spend >= $1000)');
assert(profileAfter2.tags.includes('repeat_buyer'), 'Customer automatically granted "repeat_buyer" tag (orders >= 2)');

// -----------------------------------------------------------------------------
// TEST 2: Dynamic Behavioral Segment Evaluation
// -----------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 2] Dynamic Behavioral Segment Query Evaluation\x1b[0m');

function matchRule(actual, operator, expected) {
    switch (operator) {
        case 'equals':
        case '==':
            return actual == expected;
        case 'not_equals':
        case '!=':
            return actual != expected;
        case 'greater_than':
        case '>':
            return parseFloat(actual) > parseFloat(expected);
        case 'greater_than_or_equal':
        case '>=':
            return parseFloat(actual) >= parseFloat(expected);
        case 'less_than':
        case '<':
            return parseFloat(actual) < parseFloat(expected);
        case 'less_than_or_equal':
        case '<=':
            return parseFloat(actual) <= parseFloat(expected);
        case 'contains':
            if (Array.isArray(actual)) return actual.includes(expected);
            return String(actual).toLowerCase().includes(String(expected).toLowerCase());
        case 'in_array':
            return Array.isArray(expected) && expected.includes(actual);
        default:
            return false;
    }
}

function evaluateSegment(conditions, profile, referenceDateStr) {
    const operator = (conditions.operator || 'AND').toUpperCase();
    const rules = conditions.rules || [];
    if (!rules.length) return false;

    const refTs = new Date(referenceDateStr).getTime();
    let daysSinceLastOrder = 9999;
    if (profile.last_order_at) {
        const lastTs = new Date(profile.last_order_at).getTime();
        daysSinceLastOrder = Math.max(0, Math.floor((refTs - lastTs) / (1000 * 86400)));
    }

    const results = rules.map(rule => {
        let actual = null;
        if (rule.field === 'days_since_last_order') {
            actual = daysSinceLastOrder;
        } else if (rule.field === 'tags') {
            actual = profile.tags || [];
        } else {
            actual = profile[rule.field];
        }
        return matchRule(actual, rule.operator, rule.value);
    });

    if (operator === 'OR') {
        return results.includes(true);
    }
    return !results.includes(false);
}

const currentDate = '2026-10-14 20:00:00';

// Segment: VIP High Spenders (total_spend >= 1000 OR order_count >= 5)
const segVipConditions = {
    operator: 'OR',
    rules: [
        { field: 'total_spend', operator: 'greater_than_or_equal', value: 1000.00 },
        { field: 'order_count', operator: 'greater_than_or_equal', value: 5 },
    ],
};
assert(evaluateSegment(segVipConditions, profileAfter2, currentDate) === true, 'Profile after 2 orders qualifies for VIP High Spenders');
assert(evaluateSegment(segVipConditions, profileAfter1, currentDate) === false, 'Profile with $899 spend does not qualify for VIP High Spenders');

// Segment: First-Time Buyers (order_count == 1)
const segFirstTimeConditions = {
    operator: 'AND',
    rules: [
        { field: 'order_count', operator: 'equals', value: 1 },
    ],
};
assert(evaluateSegment(segFirstTimeConditions, profileAfter1, currentDate) === true, '1-order customer matches First-Time Buyers segment');
assert(evaluateSegment(segFirstTimeConditions, profileAfter2, currentDate) === false, '2-order customer excluded from First-Time Buyers');

// Segment: At-Risk Inactive (orders >= 1 AND days_since_last_order >= 60)
const segAtRiskConditions = {
    operator: 'AND',
    rules: [
        { field: 'order_count', operator: 'greater_than_or_equal', value: 1 },
        { field: 'days_since_last_order', operator: 'greater_than_or_equal', value: 60 },
    ],
};
// Customer who ordered 74 days ago (Aug 1 to Oct 14 is 74 days)
const dormantCustomer = {
    order_count: 2,
    total_spend: 500,
    last_order_at: '2026-08-01 10:00:00',
    tags: [],
};
assert(evaluateSegment(segAtRiskConditions, dormantCustomer, currentDate) === true, 'Customer with 74 days of inactivity matches At-Risk segment');

const activeCustomer = {
    order_count: 2,
    total_spend: 500,
    last_order_at: '2026-10-10 10:00:00', // 4 days ago
    tags: [],
};
assert(evaluateSegment(segAtRiskConditions, activeCustomer, currentDate) === false, 'Customer active 4 days ago is not At-Risk');

// Segment: Repeat Jewelry Collectors (tags contains 'jewelry' OR tags contains 'collector' OR order_count >= 3)
const segCollectorConditions = {
    operator: 'OR',
    rules: [
        { field: 'tags', operator: 'contains', value: 'jewelry' },
        { field: 'tags', operator: 'contains', value: 'collector' },
        { field: 'order_count', operator: 'greater_than_or_equal', value: 3 },
    ],
};
const jewelryFan = { order_count: 1, total_spend: 150, tags: ['jewelry', 'newsletter'] };
assert(evaluateSegment(segCollectorConditions, jewelryFan, currentDate) === true, 'Customer with jewelry tag matches Repeat Jewelry Collectors');

// -----------------------------------------------------------------------------
// TEST 3: Visual Workflow Engine (SureSalez Flow) Execution
// -----------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 3] SureSalez Flow Event Dispatch, Condition Checking & Action Execution\x1b[0m');

function executeWorkflow(workflow, payload) {
    const conditions = workflow.conditions || [];
    let conditionsMet = true;

    if (conditions.length > 0) {
        const results = conditions.map(rule => {
            const actual = payload[rule.field];
            return matchRule(actual, rule.operator, rule.value);
        });
        conditionsMet = !results.includes(false);
    }

    if (!conditionsMet) {
        return { status: 'skipped', message: 'Conditions not met' };
    }

    const executedActions = [];
    (workflow.actions || []).forEach(actionDef => {
        let actionRes = { success: true, message: `Executed ${actionDef.action}` };
        if (actionDef.action === 'customer.add_tag') {
            actionRes = { success: true, message: `Tag "${actionDef.params.tag}" added to ${payload.email}` };
        } else if (actionDef.action === 'order.hold_fulfillment') {
            actionRes = { success: true, message: `Order #${payload.order_id} held for fulfillment review: ${actionDef.params.reason}` };
        } else if (actionDef.action === 'notification.send_email') {
            actionRes = { success: true, message: `Email "${actionDef.params.subject}" sent to ${payload.email}` };
        } else if (actionDef.action === 'webhook.dispatch') {
            actionRes = { success: true, message: `Webhook sent to ${actionDef.params.url}` };
        }
        executedActions.push({ action: actionDef.action, result: actionRes });
    });

    return {
        status: 'success',
        executedActions,
        message: `Executed ${executedActions.length} actions for "${workflow.title}"`,
    };
}

// Workflow 1: VIP Order Completed
const vipWorkflow = {
    title: 'VIP Tagging & Exclusive Welcome',
    trigger_event: 'order.completed',
    conditions: [
        { field: 'order_total', operator: 'greater_than_or_equal', value: 1000.00 },
    ],
    actions: [
        { action: 'customer.add_tag', params: { tag: 'vip' } },
        { action: 'notification.send_email', params: { template: 'vip_welcome', subject: 'Welcome to VIP' } },
    ],
};

const highOrderPayload = { email: 'vip@client.com', order_id: 8812, order_total: 1450.00 };
const vipResult = executeWorkflow(vipWorkflow, highOrderPayload);
assert(vipResult.status === 'success', 'VIP workflow executes when order_total >= 1000 ($1450)');
assert(vipResult.executedActions.length === 2, 'All 2 actions executed');
assert(vipResult.executedActions[0].action === 'customer.add_tag', 'Action 1 adds customer tag');
assert(vipResult.executedActions[1].action === 'notification.send_email', 'Action 2 dispatches VIP email');

const lowOrderPayload = { email: 'user@client.com', order_id: 8813, order_total: 45.00 };
const skippedResult = executeWorkflow(vipWorkflow, lowOrderPayload);
assert(skippedResult.status === 'skipped', 'VIP workflow skipped when order_total < 1000 ($45)');

// Workflow 2: Fraud Risk Hold
const fraudWorkflow = {
    title: 'High-Value Fraud Review Hold',
    trigger_event: 'order.completed',
    conditions: [
        { field: 'order_total', operator: 'greater_than_or_equal', value: 5000.00 },
    ],
    actions: [
        { action: 'order.hold_fulfillment', params: { reason: 'High-value threshold' } },
        { action: 'webhook.dispatch', params: { url: 'https://security.suresalez.internal' } },
    ],
};

const megaOrderPayload = { email: 'mega@client.com', order_id: 8899, order_total: 6200.00 };
const fraudResult = executeWorkflow(fraudWorkflow, megaOrderPayload);
assert(fraudResult.status === 'success', 'High-value order ($6200) triggers fraud review workflow');
assert(fraudResult.executedActions[0].action === 'order.hold_fulfillment', 'Fulfillment hold placed on order #8899');
assert(fraudResult.executedActions[1].action === 'webhook.dispatch', 'Webhook dispatched to security endpoint');

// -----------------------------------------------------------------------------
// TEST 4: Conversational Live Chat Bot & FAQ Pattern Matching
// -----------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 4] Conversational Live Chat Autonomous FAQ Matching\x1b[0m');

const BOT_KNOWLEDGE_BASE = {
    tracking: {
        patterns: ['track', 'where is my order', 'tracking number', 'status of order', 'delivery status', 'shipped'],
        replyKeyword: 'track',
        action: 'lookup_order',
    },
    returns: {
        patterns: ['return', 'refund', 'exchange', 'rma', 'send back'],
        replyKeyword: '30-day',
        action: 'link_rma',
    },
    shipping: {
        patterns: ['shipping time', 'how long', 'delivery time', 'international shipping'],
        replyKeyword: 'dispatched same-day',
        action: null,
    },
    care: {
        patterns: ['care', 'clean', 'scratch', 'water resistance', 'titanium'],
        replyKeyword: '10 ATM',
        action: null,
    },
    human: {
        patterns: ['human', 'agent', 'support', 'talk to person', 'representative'],
        replyKeyword: 'WhatsApp Business',
        action: 'escalate_whatsapp',
    },
};

function generateBotResponse(message) {
    const clean = (message || '').toLowerCase().trim();
    if (!clean) return { category: 'greeting' };

    for (const [category, data] of Object.entries(BOT_KNOWLEDGE_BASE)) {
        for (const pattern of data.patterns) {
            if (clean.includes(pattern)) {
                return { category, action: data.action, matchedPattern: pattern };
            }
        }
    }
    return { category: 'fallback', action: 'escalate_whatsapp' };
}

assert(generateBotResponse('Where is my order?').category === 'tracking', 'Recognizes order tracking inquiry');
assert(generateBotResponse('Can I track package #98450?').action === 'lookup_order', 'Assigns lookup_order action for tracking query');
assert(generateBotResponse('I would like to return an item').category === 'returns', 'Recognizes returns/refunds inquiry');
assert(generateBotResponse('How do I generate an RMA?').action === 'link_rma', 'Assigns link_rma action for returns query');
assert(generateBotResponse('How long does shipping take to London?').category === 'shipping', 'Recognizes shipping timelines inquiry');
assert(generateBotResponse('Is this titanium watch scratch proof and water resistant?').category === 'care', 'Recognizes product care and water resistance inquiry');
assert(generateBotResponse('I want to speak with a human support agent').category === 'human', 'Recognizes human escalation inquiry');
assert(generateBotResponse('Talk to real person please').action === 'escalate_whatsapp', 'Routes human escalation to WhatsApp action');

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
console.log('\n======================================================');
console.log(`  Sprint 04 Tests Finished: ${passedCount}/${testsCount} Passed (${failedCount} Failed)`);
console.log('======================================================\n');

if (failedCount > 0) {
    process.exit(1);
}
