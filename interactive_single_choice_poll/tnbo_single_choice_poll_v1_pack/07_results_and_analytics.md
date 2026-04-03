# 07. Results and Analytics

## 7.1 Result Logic

Single-choice results are straightforward:

- each valid vote increments one option
- poll totals are sum of all valid votes
- percentages are derived from total votes
- winner is option with highest vote count

## 7.2 Tie Handling

Recommended V1 tie behavior:
- allow ties in raw data
- show tied options if needed
- do not force tie-break logic unless business case exists

If a single winner is needed later, tie-break can be defined separately.

## 7.3 Suggested Metrics

Track at minimum:
- poll impressions
- unique viewers
- vote submissions
- unique voters
- option click/select interactions
- poll shares
- conversion rate (views to votes)

## 7.4 Recommended Event Names

Suggested analytics events:
- `poll_viewed`
- `poll_option_selected`
- `poll_vote_submitted`
- `poll_results_viewed`
- `poll_shared`

Suggested common fields:
- `poll_id`
- `poll_type`
- `poll_category`
- `option_id` where relevant
- `client`
- `session_id`
- `user_id`
- `context_type`
- `context_id`
- `metadata`

## 7.5 Dashboard Reporting Suggestions

Useful dashboard summaries:
- most voted polls
- most viewed polls
- highest conversion polls
- top-performing categories
- top-performing surfaces (home, article, match, campaign)

## 7.6 BFF / App Usage Suggestions

BFF can use analytics and flags to power:
- featured poll slots
- trending poll widgets
- polls attached to a match or article
- recap cards after poll closes

## 7.7 Sponsor-Friendly Reporting

This module is good for sponsorship because admins can later export:
- poll title
- participation count
- reach/impressions
- winning option
- voting duration
- engagement by surface

That makes the module commercially useful beyond fan engagement.
