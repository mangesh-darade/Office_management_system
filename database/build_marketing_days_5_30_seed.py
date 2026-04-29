# -*- coding: utf-8 -*-
"""Generate marketing_days_5_30_questions_seed.sql — run: python build_marketing_days_5_30_seed.py"""
import io
import os

# assessment_id = day_number + 3 (Day 3=6, Day 4=7 pattern)
def aid(day):
    return day + 3

def esc(s):
    return s.replace("\\", "\\\\").replace("'", "''")

def emit_mcq(out, sort_order, q, opts, correct_mask):
    """opts: 4 strings; correct_mask: tuple of 4 ints 0/1"""
    out.write(
        "INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)\n"
        "VALUES (@aid,'mcq','%s',1.00,NULL,NULL,50.00,NULL,%d,NOW(),NOW());\n"
        % (esc(q), sort_order)
    )
    out.write("SET @q := LAST_INSERT_ID();\n")
    out.write("INSERT INTO `ta_question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`,`created_at`) VALUES\n")
    parts = []
    for i, o in enumerate(opts):
        parts.append("(@q,'%s',%d,%d,NOW())" % (esc(o), correct_mask[i], i + 1))
    out.write(",\n".join(parts) + ";\n\n")

def emit_text(out, sort_order, q, keywords_csv):
    out.write(
        "INSERT INTO `ta_questions` (`assessment_id`,`question_type`,`question_text`,`points`,`coding_language`,`model_answer`,`text_keyword_pass_percent`,`coding_expected_output`,`sort_order`,`created_at`,`updated_at`)\n"
        "VALUES (@aid,'text','%s',2.00,NULL,'%s',50.00,NULL,%d,NOW(),NOW());\n\n"
        % (esc(q), esc(keywords_csv), sort_order)
    )

def parse_correct(c):
    c = c.strip().upper()
    if len(c) == 1 and c in "ABCD":
        idx = ord(c) - ord("A")
        return tuple(1 if i == idx else 0 for i in range(4))
    mask = [0, 0, 0, 0]
    for ch in c:
        if ch in "ABCD":
            mask[ord(ch) - ord("A")] = 1
    return tuple(mask)

def line_mcq(row):
    """row: question|o1|o2|o3|o4|CORRECT e.g. B or ABC"""
    parts = row.split("|")
    if len(parts) != 6:
        raise ValueError("Bad MCQ row: %r" % row)
    q, o1, o2, o3, o4, cor = parts
    return q, [o1, o2, o3, o4], parse_correct(cor)


# --- Day 13 text (keywords = comma-separated for model_answer) ---
DAY13_TEXT = [
    ("Explain what LinkedIn Ads and Amazon Ads are and their importance.", "linkedin ads,amazon ads,b2b marketing,e-commerce ads,targeting"),
    ("What are the different ad formats available in LinkedIn Ads?", "linkedin ad formats,sponsored content,text ads,message ads,video ads"),
    ("What are the different types of Amazon Ads?", "amazon ads types,sponsored products,sponsored brands,sponsored display"),
    ("Explain targeting options in LinkedIn Ads.", "linkedin targeting,job title,industry,company size,skills"),
    ("What metrics are important in LinkedIn and Amazon Ads?", "ctr,cpc,conversion rate,roi,ad metrics"),
    ("A company wants to generate B2B leads. Which platform should they use and why?", "b2b leads,linkedin ads,professional audience,targeting"),
    ("An e-commerce business wants to boost product sales. Which ads should they use?", "e-commerce ads,amazon ads,product promotion,sales"),
    ("A LinkedIn campaign has low engagement. What should be improved?", "linkedin ads,engagement,creatives,targeting"),
    ("An Amazon ad gets clicks but no purchases. What is the issue?", "conversion issue,product page,pricing,reviews"),
    ("A startup wants to target professionals in IT industry. Which strategy should they use?", "linkedin targeting,job title,industry,audience strategy"),
]

# --- MCQ days: each value is list of "q|o1|o2|o3|o4|CORRECT" ---
DAYS_MCQ = {}

DAYS_MCQ[5] = [
"What is competitor analysis?|Studying customers|Studying competitors|Selling products|Advertising|B",
"Which type of competitor sells similar products?|Direct|Indirect|Substitute|None|A",
"Which competitor offers alternative solutions?|Direct|Indirect|Substitute|Same|C",
"What does SWOT stand for?|Strength Weakness Opportunity Threat|Sales Work Operation Task|Strategy Work Output Target|None|A",
"Which factor is important in competitor analysis?|Pricing|Product|Marketing|All of the above|D",
"Which helps identify competitor strategies?|Website analysis|Ads tracking|Social media|All of the above|D",
"Which is NOT part of competitor analysis?|Customer feedback|Pricing|Random guessing|Product features|C",
"Which tool helps in SEO competitor analysis?|Analytics|SEO tools|CRM|None|B",
"Which improves competitive advantage?|Differentiation|Copying blindly|Ignoring competitors|Random strategy|A",
"Which helps identify market gaps?|Competitor analysis|Random ads|Guesswork|None|A",
"Select all correct: Competitor types include|Direct|Indirect|Substitute|Internal|ABC",
"Select all correct: SWOT includes|Strengths|Weaknesses|Opportunities|Targets|ABC",
"Which is first step in competitor analysis?|Identify competitors|Pricing|Ads|Sales|A",
"Which helps track competitor ads?|Ad libraries|CRM|HR tools|None|A",
"Which factor affects competition most?|Price|Quality|Brand|All of the above|D",
"Which improves market position?|Unique strategy|Copying|Ignoring|Random|A",
"Which data is useful for analysis?|Reviews|Feedback|Ratings|All of the above|D",
"Which helps in benchmarking?|Competitor analysis|Guesswork|Random ads|None|A",
"Which is a benefit of competitor analysis?|Better strategy|Market insight|Risk reduction|All of the above|D",
"Which improves decision making?|Data analysis|Guesswork|Ignoring data|Random|A",
]

DAYS_MCQ[6] = [
"What does KPI stand for?|Key Performance Indicator|Key Process Input|Knowledge Performance Index|Key Product Indicator|A",
"Which is an example of a KPI?|Conversion Rate|Office location|Employee name|Random data|A",
"What does SMART stand for?|Specific Measurable Achievable Relevant Time-bound|Simple Measured Accurate Real Time|Strategic Market Analysis Real Target|None|A",
"Which KPI measures ad clicks?|CTR|ROI|CPC|Bounce Rate|A",
"Which KPI measures profit?|CTR|ROI|Impressions|Clicks|B",
"Which KPI measures cost per click?|CPC|CTR|ROI|CPM|A",
"Which KPI measures engagement?|Likes|Shares|Comments|All of the above|D",
"Which is NOT a KPI?|Revenue|Conversion Rate|Random guess|CTR|C",
"Which KPI is used for awareness?|Impressions|ROI|Sales|Profit|A",
"Which KPI measures conversions?|Conversion Rate|CTR|CPC|Reach|A",
"Select all correct: SMART goals include|Specific|Measurable|Achievable|Random|ABC",
"Select all correct: Digital KPIs include|CTR|ROI|CPC|Employee ID|ABC",
"Which improves KPI performance?|Optimization|Data analysis|Strategy improvement|All of the above|D",
"Which tool helps track KPIs?|Analytics tools|CRM|Dashboards|All of the above|D",
"Which KPI measures audience reach?|Reach|CTR|CPC|ROI|A",
"Which KPI helps in decision making?|Data metrics|Guesswork|Random|None|A",
"Which is first step in KPI setup?|Define goals|Run ads|Sales|Marketing|A",
"Which improves ROI?|Better targeting|Optimization|Data analysis|All of the above|D",
"Which KPI tracks user actions?|Conversion|Impressions|Reach|Views|A",
"Which KPI is most important for sales success?|Conversion Rate|Likes|Views|Shares|A",
]

DAYS_MCQ[7] = [
"What is strategic thinking?|Random decisions|Long-term planning|Guesswork|Ignoring data|B",
"Which connects all marketing activities?|Strategy|Random actions|Ads only|Sales only|A",
"Which is first step in strategy building?|Research|Sales|Ads|Execution|A",
"Which ensures success measurement?|KPIs|Guesswork|Random|None|A",
"Which concept helps define audience?|STP|KPI|Funnel|Sales|A",
"Which stage focuses on conversion?|Awareness|Consideration|Conversion|Research|C",
"Which improves marketing results?|Optimization|Ignoring data|Guesswork|Random|A",
"Which helps in decision making?|Data|Guesswork|Random|None|A",
"Which is NOT part of strategy?|Planning|Execution|Random actions|Measurement|C",
"Which ensures alignment with goals?|Strategy|Random ads|Guesswork|None|A",
"Select all correct: Strategy includes|Planning|Execution|Measurement|Ignoring data|ABC",
"Select all correct: Good strategy practices include|Data analysis|Goal setting|Optimization|Random actions|ABC",
"Which improves ROI?|Strategy|Random ads|No planning|Guesswork|A",
"Which connects STP funnel and KPIs?|Strategy|Ads|Sales|Random|A",
"Which helps improve conversions?|Funnel optimization|Ignoring users|Random targeting|None|A",
"Which helps in long-term success?|Planning|Guesswork|Random ads|None|A",
"Which is key to competitive advantage?|Strategy|Copying|Ignoring|Random|A",
"Which improves campaign performance?|Data-driven strategy|Guesswork|Random ads|None|A",
"Which step comes after execution?|Measurement|Planning|Research|None|A",
"Which ensures continuous improvement?|Optimization|Ignoring results|Random|None|A",
]

DAYS_MCQ[8] = [
"What is paid advertising?|Free marketing|Paid promotion|Organic growth|SEO|B",
"Which platform is used for search ads?|Google Ads|Instagram|LinkedIn|Email|A",
"Which type of ad appears on websites?|Search|Display|Social|Video|B",
"What does CPC stand for?|Cost Per Click|Cost Per Conversion|Click Per Cost|Conversion Per Click|A",
"What does CTR measure?|Click rate|Conversion|Cost|Reach|A",
"Which platform is used for social media ads?|Facebook|Google|Email|CRM|A",
"Which metric measures cost per 1000 impressions?|CPC|CPM|CTR|ROI|B",
"Which is NOT a paid channel?|SEO|Google Ads|Facebook Ads|YouTube Ads|A",
"Which factor improves ad performance?|Targeting|Creatives|Budget|All of the above|D",
"Which helps increase conversions?|Good landing page|Weak CTA|Random ads|None|A",
"Select all correct: Paid ad types include|Search|Display|Video|Organic|ABC",
"Select all correct: Key metrics include|CTR|CPC|CPM|Employee ID|ABC",
"Which improves ROI?|Optimization|Ignoring data|Guesswork|Random|A",
"Which is first step in ad campaign?|Define goal|Run ads|Sales|Budget|A",
"Which improves click rate?|Good creatives|Bad copy|Wrong targeting|None|A",
"Which platform supports video ads?|YouTube|Google|Facebook|All of the above|D",
"Which KPI measures success?|ROI|Guesswork|Random|None|A",
"Which improves targeting?|Audience segmentation|Random ads|No data|Guesswork|A",
"Which is goal of paid ads?|Reach audience|Ignore users|Random traffic|None|A",
"Which helps optimize ads?|A/B testing|Guesswork|Random|None|A",
]

DAYS_MCQ[9] = [
"What are Meta Ads?|Free marketing|Paid ads on Facebook and Instagram|SEO|Email marketing|B",
"Which platform is part of Meta Ads?|Facebook|Instagram|Both|None|C",
"What is the first level in Meta Ads structure?|Ad|Campaign|Ad Set|Creative|B",
"Which level defines targeting?|Campaign|Ad Set|Ad|Budget|B",
"Which ad format shows multiple images?|Image|Video|Carousel|Text|C",
"What does CPC stand for?|Cost Per Click|Cost Per Campaign|Click Per Cost|Conversion Per Click|A",
"Which metric measures clicks?|CTR|CPM|ROI|Reach|A",
"Which is used for retargeting?|Custom audience|Random audience|Broad targeting|None|A",
"Which helps find similar users?|Custom audience|Lookalike audience|Random targeting|None|B",
"Which improves ad performance?|Creatives|Targeting|Optimization|All of the above|D",
"Select all correct: Ad formats include|Image|Video|Carousel|Email|ABC",
"Select all correct: Targeting options include|Demographics|Interests|Behavior|Random|ABC",
"Which metric measures cost per 1000 impressions?|CPC|CPM|CTR|ROI|B",
"Which improves conversions?|Strong CTA|Weak content|Random ads|None|A",
"Which helps reduce ad cost?|Optimization|Ignoring data|Guesswork|Random|A",
"Which is goal of awareness campaign?|Reach|Sales|Conversion|Leads|A",
"Which KPI measures success?|ROI|Guesswork|Random|None|A",
"Which improves engagement?|Creative content|Bad design|Wrong targeting|None|A",
"Which step comes after campaign setup?|Optimization|Measurement|Launch|Planning|C",
"Which ensures better targeting?|Audience segmentation|Random ads|No data|Guesswork|A",
]

DAYS_MCQ[10] = [
"What is ad creative?|Budget|Visual content|Data|Sales|B",
"Which element attracts attention most?|Image/Video|Text|Budget|Data|A",
"What is the role of CTA?|Ignore user|Guide action|Reduce clicks|Random|B",
"Which is part of campaign structure?|Campaign|Ad Set|Ad|All of the above|D",
"Which improves CTR?|Strong creatives|Weak copy|Random ads|None|A",
"What does A/B testing mean?|Random ads|Compare variations|Copy ads|Ignore data|B",
"Which improves engagement?|Good visuals|Bad design|No CTA|None|A",
"Which is NOT a good copy element?|Clear message|Benefits|Confusing text|CTA|C",
"Which helps conversion?|Strong CTA|Weak message|Random ads|None|A",
"Which platform uses creatives heavily?|Facebook|Instagram|Both|None|C",
"Select all correct: Creative elements include|Image|Video|CTA|Random data|ABC",
"Select all correct: Good copy includes|Benefits|Emotional appeal|Clear CTA|Confusion|ABC",
"Which improves ad relevance?|Targeting|Creatives|Copy|All of the above|D",
"Which reduces CPC?|Optimization|Ignoring data|Random ads|None|A",
"Which stage includes ad creation?|Campaign|Ad Set|Ad|None|C",
"Which improves ROI?|Testing|Optimization|Better creatives|All of the above|D",
"Which ensures better performance?|Data analysis|Guesswork|Random ads|None|A",
"Which helps improve ads continuously?|A/B testing|Ignoring results|Random|None|A",
"Which is first step in ad creation?|Define goal|Run ads|Sales|Budget|A",
"Which improves click rate most?|Creative + Copy|Budget|Random ads|None|A",
]

DAYS_MCQ[11] = [
"What is Google Ads?|Free tool|Paid advertising platform|SEO|Email|B",
"Where do Search Ads appear?|Websites|Search results|Email|CRM|B",
"Where do Display Ads appear?|Search page|Websites|Email|CRM|B",
"What is a keyword?|Random text|Search term|Ad copy|Image|B",
"Which match type gives maximum reach?|Broad|Phrase|Exact|None|A",
"Which match type is most precise?|Broad|Phrase|Exact|Random|C",
"Which metric measures clicks?|CTR|CPC|ROI|CPM|A",
"Which metric measures cost per click?|CPC|CTR|ROI|CPM|A",
"Which improves ad ranking?|Quality Score|Random|Guesswork|None|A",
"Which helps increase conversions?|Good landing page|Weak CTA|Random ads|None|A",
"Select all correct: Campaign components include|Campaign|Ad group|Keywords|Email|ABC",
"Select all correct: Match types include|Broad|Phrase|Exact|Manual|ABC",
"Which improves ROI?|Optimization|Ignoring data|Random ads|None|A",
"Which tool is used for keyword research?|Keyword Planner|CRM|HR tool|None|A",
"Which campaign is best for intent-based users?|Search|Display|Random|None|A",
"Which improves CTR?|Strong ad copy|Weak content|Random ads|None|A",
"Which is goal of display ads?|Awareness|Sales only|Random|None|A",
"Which improves targeting?|Keywords|Random ads|No data|Guesswork|A",
"Which step comes after campaign setup?|Launch|Planning|Research|None|A",
"Which ensures continuous improvement?|Optimization|Ignoring results|Random|None|A",
]

DAYS_MCQ[12] = [
"What is bidding in Google Ads?|Free clicks|Ad auction process|SEO|Email|B",
"Which bidding strategy focuses on clicks?|Target CPA|Maximize Clicks|Target ROAS|Manual|B",
"Which bidding strategy focuses on conversions?|Maximize Clicks|Target CPA|CPM|Manual|B",
"What does ROAS stand for?|Return On Ad Spend|Rate Of Ad Sales|Revenue On Ad System|None|A",
"Which budget type is used in campaigns?|Daily budget|Weekly|Monthly|None|A",
"Which helps control cost?|Budget|Random ads|Guesswork|None|A",
"Which strategy is automated?|Manual CPC|Smart bidding|Random|None|B",
"Which is best for beginners?|Smart campaigns|Manual|Complex setup|None|A",
"Which improves ROI?|Optimization|Ignoring data|Random ads|None|A",
"Which metric helps measure success?|ROI|Guesswork|Random|None|A",
"Select all correct: Bidding strategies include|Manual CPC|Maximize Clicks|Target CPA|Email|ABC",
"Select all correct: Budget strategies include|Daily budget|Allocation|Cost control|Random|ABC",
"Which improves performance?|Data analysis|Guesswork|Random ads|None|A",
"Which helps automation?|Smart bidding|Manual|Random|None|A",
"Which improves conversions?|Bid optimization|Ignoring users|Random ads|None|A",
"Which ensures better budget use?|Optimization|Guesswork|Random|None|A",
"Which is first step in campaign budgeting?|Define goal|Run ads|Sales|Random|A",
"Which improves ad rank?|Bid + Quality Score|Random|Guesswork|None|A",
"Which helps reduce cost?|Optimization|Ignoring data|Random ads|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring results|Random|None|A",
]

DAYS_MCQ[13] = [
"Which platform is best for B2B advertising?|Amazon|LinkedIn|Instagram|YouTube|B",
"Which platform is best for product selling?|LinkedIn|Amazon|Twitter|Email|B",
"Which LinkedIn ad format appears in feed?|Sponsored content|Text ads|Email|CRM|A",
"Which Amazon ad type promotes products?|Sponsored products|Sponsored brands|Sponsored display|All of the above|D",
"Which targeting is available in LinkedIn?|Job title|Industry|Skills|All of the above|D",
"Which metric measures clicks?|CTR|ROI|CPM|Reach|A",
"Which improves conversion?|Good product page|Weak reviews|High price|None|A",
"Which is NOT LinkedIn ad type?|Message ads|Sponsored content|Search ads|Text ads|C",
"Which helps improve Amazon sales?|Reviews|Pricing|Product listing|All of the above|D",
"Which helps targeting professionals?|LinkedIn Ads|Amazon Ads|Random ads|None|A",
"Select all correct: LinkedIn targeting includes|Job title|Industry|Skills|Random|ABC",
"Select all correct: Amazon ad types include|Sponsored products|Sponsored brands|Sponsored display|Email|ABC",
"Which improves ROI?|Optimization|Guesswork|Random ads|None|A",
"Which helps engagement?|Good creatives|Bad content|Random ads|None|A",
"Which step is first in campaign setup?|Define goal|Run ads|Sales|Random|A",
"Which improves ad performance?|Targeting|Creatives|Optimization|All of the above|D",
"Which KPI measures success?|ROI|Guesswork|Random|None|A",
"Which improves brand awareness?|Ads|Ignoring users|Random|None|A",
"Which helps generate leads?|LinkedIn Ads|Amazon Ads|Random|None|A",
"Which ensures continuous improvement?|Optimization|Ignoring results|Random|None|A",
]

DAYS_MCQ[14] = [
"What does ROI stand for?|Return On Investment|Rate Of Interest|Revenue On Input|None|A",
"What does ROAS stand for?|Return On Ad Spend|Rate Of Ad Sales|Revenue On Ad System|None|A",
"Which measures profitability?|ROI|CTR|CPC|CPM|A",
"Which measures ad performance?|ROAS|CTR|CPC|Reach|A",
"Which factor affects ROI?|Cost|Revenue|Conversion rate|All of the above|D",
"Which helps control spending?|Budget|Random ads|Guesswork|None|A",
"Which improves ROI?|Optimization|Ignoring data|Random ads|None|A",
"Which is NOT part of budgeting?|Planning|Allocation|Random spending|Tracking|C",
"Which metric compares revenue to ad spend?|ROAS|ROI|CTR|CPC|A",
"Which improves profitability?|Reduce cost|Increase revenue|Optimization|All of the above|D",
"Select all correct: Budgeting includes|Planning|Allocation|Tracking|Random|ABC",
"Select all correct: ROI factors include|Cost|Revenue|Conversion|Random|ABC",
"Which improves campaign efficiency?|Budget optimization|Guesswork|Random ads|None|A",
"Which helps measure success?|ROI|Guesswork|Random|None|A",
"Which step comes first in budgeting?|Define goals|Spend money|Random ads|None|A",
"Which improves ROAS?|Better targeting|Optimization|Data analysis|All of the above|D",
"Which reduces waste?|Optimization|Ignoring data|Random ads|None|A",
"Which ensures better decisions?|Data analysis|Guesswork|Random|None|A",
"Which improves marketing strategy?|ROI insights|Guesswork|Random ads|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring results|Random|None|A",
]

DAYS_MCQ[15] = [
"What does SEO stand for?|Search Engine Optimization|Search Engine Operation|Site Engine Optimization|None|A",
"Which type of SEO focuses on website content?|On-page|Off-page|Technical|None|A",
"Which type of SEO involves backlinks?|On-page|Off-page|Technical|None|B",
"Which SEO type improves site speed and structure?|On-page|Off-page|Technical|None|C",
"Which search result is unpaid?|Organic|Paid|Ads|Sponsored|A",
"Which element is important for SEO?|Keywords|Content|Links|All of the above|D",
"Which metric measures traffic from search engines?|Organic traffic|CTR|CPC|ROI|A",
"Which improves ranking?|Backlinks|Random ads|Guesswork|None|A",
"Which affects user experience?|Page speed|Content|Design|All of the above|D",
"Which is NOT part of SEO?|Keywords|Content|Paid ads|Backlinks|C",
"Select all correct: SEO types include|On-page|Off-page|Technical|Email|ABC",
"Select all correct: On-page SEO includes|Content|Meta tags|Internal links|Random|ABC",
"Which improves visibility?|SEO|Ignoring data|Random ads|None|A",
"Which helps indexing?|Sitemap|Robots.txt|Search Console|All of the above|D",
"Which improves CTR?|Meta title|Meta description|Rich snippets|All of the above|D",
"Which helps keyword research?|SEO tools|CRM|HR tools|None|A",
"Which improves engagement?|Relevant content|Poor content|Random ads|None|A",
"Which is long-term strategy?|SEO|Paid ads|Random|None|A",
"Which improves ranking continuously?|Optimization|Ignoring data|Random|None|A",
"Which ensures better results?|Data analysis|Guesswork|Random|None|A",
]

DAYS_MCQ[16] = [
"What is keyword research?|Finding search terms|Writing ads|Designing site|Coding|A",
"Which keyword has lower competition?|Short-tail|Long-tail|Branded|None|B",
"Which keyword has high search volume?|Short-tail|Long-tail|Transactional|None|A",
"Which intent is for buying?|Informational|Navigational|Transactional|None|C",
"Which tool is used for keyword research?|Keyword Planner|CRM|HR tool|None|A",
"Which factor affects ranking?|Competition|Relevance|Volume|All of the above|D",
"Which keyword type targets specific queries?|Short-tail|Long-tail|Broad|None|B",
"Which improves conversions?|Transactional keywords|Informational keywords|Random|None|A",
"Which is NOT keyword type?|Branded|Non-branded|Random|Long-tail|C",
"Which improves traffic quality?|Relevant keywords|Random keywords|No keywords|None|A",
"Select all correct: Keyword types include|Short-tail|Long-tail|Branded|Email|ABC",
"Select all correct: Search intent includes|Informational|Navigational|Transactional|Random|ABC",
"Which improves ranking chances?|Low competition keywords|High competition only|Random|None|A",
"Which tool helps find keyword difficulty?|SEO tools|CRM|HR|None|A",
"Which improves SEO strategy?|Keyword research|Guesswork|Random ads|None|A",
"Which step comes first?|Identify keywords|Write content|Publish site|None|A",
"Which improves engagement?|Matching intent|Ignoring users|Random|None|A",
"Which ensures better results?|Data analysis|Guesswork|Random|None|A",
"Which improves SEO performance?|Optimization|Ignoring data|Random|None|A",
"Which helps long-term growth?|SEO strategy|Paid ads only|Random|None|A",
]

DAYS_MCQ[17] = [
"What is On-Page SEO?|Off-site SEO|Content optimization|Paid ads|Email|B",
"Which element improves CTR?|Meta title|URL|Code|Hosting|A",
"Where should keywords be placed?|Title|Headings|Content|All of the above|D",
"What improves readability?|Headings|Paragraphs|Formatting|All of the above|D",
"Which is NOT On-Page SEO?|Content|Keywords|Backlinks|Meta tags|C",
"Which helps indexing?|Internal links|Random ads|Guesswork|None|A",
"Which improves user experience?|Fast loading|Poor design|Random content|None|A",
"What is keyword stuffing?|Proper use|Overuse of keywords|No keywords|Random|B",
"Which improves ranking?|Optimization|Guesswork|Random ads|None|A",
"Which improves structure?|Headings|Random text|No format|None|A",
"Select all correct: On-page elements include|Content|Meta tags|Headings|Email|ABC",
"Select all correct: SEO practices include|Keyword placement|Internal linking|Optimization|Random|ABC",
"Which improves engagement?|Quality content|Poor content|Random|None|A",
"Which improves SEO performance?|Optimization|Ignoring data|Random|None|A",
"Which helps search engines understand content?|Keywords|Random|Guesswork|None|A",
"Which improves click rate?|Meta description|Code|Random|None|A",
"Which step comes first?|Keyword research|Publish|Ads|None|A",
"Which ensures better results?|Data analysis|Guesswork|Random|None|A",
"Which improves long-term growth?|SEO|Paid ads|Random|None|A",
"Which ensures continuous improvement?|Optimization|Ignoring results|Random|None|A",
]

DAYS_MCQ[18] = [
"What is Off-Page SEO?|On-site optimization|External SEO|Paid ads|Coding|B",
"What is a backlink?|Internal link|External link from other site|Ad|Image|B",
"Which improves authority?|Backlinks|Random ads|Guesswork|None|A",
"What is Technical SEO?|Coding only|Website optimization|Ads|Email|B",
"Which helps indexing?|Sitemap|Random ads|Guesswork|None|A",
"Which file controls crawling?|Robots.txt|Sitemap|HTML|CSS|A",
"Which improves page speed?|Optimization|Heavy files|Random|None|A",
"Which improves mobile experience?|Responsive design|Large images|Slow site|None|A",
"Which is NOT Off-Page SEO?|Backlinks|Guest posting|Meta tags|Social sharing|C",
"Which improves rankings?|SEO|Guesswork|Random ads|None|A",
"Select all correct: Off-page techniques include|Backlinks|Guest posting|Social sharing|Coding|ABC",
"Select all correct: Technical SEO elements include|Page speed|Mobile friendly|Sitemap|Email|ABC",
"Which improves visibility?|SEO|Ignoring data|Random|None|A",
"Which ensures better performance?|Optimization|Guesswork|Random|None|A",
"Which helps search engines crawl?|Robots.txt|Random|Guesswork|None|A",
"Which improves user experience?|Fast site|Slow site|Random|None|A",
"Which improves authority score?|Backlinks|Random ads|Guesswork|None|A",
"Which step comes first?|Technical audit|Ads|Sales|None|A",
"Which ensures long-term growth?|SEO|Paid ads|Random|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring results|Random|None|A",
]

DAYS_MCQ[19] = [
"What is content strategy?|Random posting|Planned content approach|Ads only|None|B",
"What is a content calendar?|Random plan|Content schedule|Budget tool|None|B",
"Which improves consistency?|Planning|Random posting|No schedule|None|A",
"Which content type is most engaging?|Video|Random|None|Ignore|A",
"Which helps understand audience?|Research|Guesswork|Random|None|A",
"Which improves reach?|Distribution|No sharing|Random|None|A",
"Which is NOT content type?|Blog|Video|Email|Random|D",
"Which improves engagement?|Relevant content|Poor content|Random|None|A",
"Which aligns content with goals?|Strategy|Guesswork|Random|None|A",
"Which improves planning?|Calendar|Random|Guesswork|None|A",
"Select all correct: Content types include|Blogs|Videos|Social posts|Random|ABC",
"Select all correct: Strategy elements include|Audience|Goals|Distribution|Random|ABC",
"Which improves performance?|Data analysis|Guesswork|Random|None|A",
"Which ensures consistency?|Calendar|Random|Guesswork|None|A",
"Which improves ROI?|Strategy|Random posting|No plan|None|A",
"Which step comes first?|Define audience|Post content|Random|None|A",
"Which improves long-term growth?|Strategy|Random|Paid ads only|None|A",
"Which ensures better results?|Planning|Guesswork|Random|None|A",
"Which improves quality?|Content optimization|Random|Guesswork|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring results|Random|None|A",
]

DAYS_MCQ[20] = [
"What is SEO blog writing?|Random writing|Optimized writing|Paid ads|Coding|B",
"Which is first element of blog?|Title|Body|Conclusion|Ads|A",
"Which improves readability?|Headings|Long paragraphs|No format|None|A",
"Which is NOT best practice?|Keyword stuffing|Quality content|Internal links|Formatting|A",
"Which helps SEO?|Keywords|Content|Links|All of the above|D",
"Where should keywords be placed?|Title|Headings|Content|All of the above|D",
"Which improves engagement?|Valuable content|Poor content|Random|None|A",
"Which reduces bounce rate?|Good readability|Poor design|Random|None|A",
"Which improves ranking?|Optimization|Guesswork|Random|None|A",
"Which helps user experience?|Clear structure|Confusing text|Random|None|A",
"Select all correct: Blog elements include|Title|Headings|Content|Random|ABC",
"Select all correct: SEO practices include|Keywords|Internal links|Quality content|Random|ABC",
"Which improves performance?|Data analysis|Guesswork|Random|None|A",
"Which ensures consistency?|Planning|Random|Guesswork|None|A",
"Which improves traffic?|SEO|Paid ads only|Random|None|A",
"Which step comes first?|Keyword research|Publish|Random|None|A",
"Which improves long-term growth?|SEO|Ads only|Random|None|A",
"Which ensures better results?|Planning|Guesswork|Random|None|A",
"Which improves content quality?|Optimization|Random|Guesswork|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring|Random|None|A",
]

DAYS_MCQ[21] = [
"What is high-performing content?|Random posts|Engaging content|Ads only|None|B",
"Which element attracts users most?|Visuals|Text only|Random|None|A",
"Which content type performs best?|Video|Text|Random|None|A",
"What improves engagement?|CTA|No interaction|Random|None|A",
"Which helps reach audience?|Relevant content|Poor content|Random|None|A",
"Which is NOT good practice?|Consistency|Planning|Random posting|Strategy|C",
"Which improves brand visibility?|Regular posting|No posting|Random|None|A",
"Which improves follower growth?|Engagement|Ignoring users|Random|None|A",
"Which helps content performance?|Trends|Random|Guesswork|None|A",
"Which improves user interaction?|Questions, polls|No CTA|Random|None|A",
"Select all correct: Content types include|Videos|Reels|Stories|Random|ABC",
"Select all correct: Performance factors include|Timing|Trends|Quality|Random|ABC",
"Which improves performance?|Data analysis|Guesswork|Random|None|A",
"Which ensures consistency?|Content calendar|Random|Guesswork|None|A",
"Which improves ROI?|Strategy|Random posting|No plan|None|A",
"Which step comes first?|Audience research|Posting|Random|None|A",
"Which improves long-term growth?|Strategy|Random|Paid ads only|None|A",
"Which ensures better results?|Planning|Guesswork|Random|None|A",
"Which improves content quality?|Optimization|Random|Guesswork|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring|Random|None|A",
]

DAYS_MCQ[22] = [
"What is GA4?|CRM tool|Analytics tool|Email tool|SEO tool|B",
"What does GA4 track?|User data|Random data|Ads only|None|A",
"Which model does GA4 use?|Session-based|Event-based|Random|None|B",
"What is an event?|User interaction|Ad|Email|Code|A",
"Which metric measures users?|Users|CPC|CTR|ROI|A",
"Which metric measures engagement?|Engagement rate|CPC|CTR|CPM|A",
"Which helps track conversions?|Events|Random|Guesswork|None|A",
"Which report shows traffic sources?|Acquisition|Behavior|Random|None|A",
"Which improves performance?|Data analysis|Guesswork|Random|None|A",
"Which is NOT GA4 metric?|Users|Sessions|Engagement rate|Random|D",
"Select all correct: GA4 metrics include|Users|Sessions|Engagement|Email|ABC",
"Select all correct: GA4 features include|Event tracking|Cross-platform|Data insights|Random|ABC",
"Which improves decision making?|Data insights|Guesswork|Random|None|A",
"Which helps understand users?|Behavior analysis|Guesswork|Random|None|A",
"Which improves ROI?|Optimization|Ignoring data|Random|None|A",
"Which step comes first?|Setup tracking|Run ads|Sales|None|A",
"Which improves campaign performance?|Data-driven strategy|Guesswork|Random|None|A",
"Which ensures better results?|Monitoring|Ignoring|Random|None|A",
"Which improves long-term growth?|Analytics|Random|Ads only|None|A",
"Which ensures continuous improvement?|Optimization|Ignoring results|Random|None|A",
]

DAYS_MCQ[23] = [
"What is an event?|User action|Ad|Email|Code|A",
"What is a conversion?|Random action|Goal completion|Click only|None|B",
"Which is an example of event?|Click|Sale|Signup|None|A",
"Which is an example of conversion?|Scroll|Purchase|Click|Page view|B",
"Which improves conversions?|CTA|No action|Random|None|A",
"Which affects user experience?|Page speed|Content|Design|All of the above|D",
"Which helps track user behavior?|Analytics|Guesswork|Random|None|A",
"Which improves conversion rate?|Optimization|Guesswork|Random|None|A",
"Which is NOT conversion?|Purchase|Signup|Random click|Lead form|C",
"Which helps understand funnel?|Funnel analysis|Guesswork|Random|None|A",
"Select all correct: Events include|Clicks|Scrolls|Page views|Email|ABC",
"Select all correct: Conversion factors include|UX|CTA|Content|Random|ABC",
"Which improves performance?|Data analysis|Guesswork|Random|None|A",
"Which ensures better results?|Monitoring|Ignoring|Random|None|A",
"Which improves ROI?|Optimization|Guesswork|Random|None|A",
"Which step comes first?|Define goals|Run ads|Sales|None|A",
"Which improves strategy?|Insights|Guesswork|Random|None|A",
"Which improves long-term growth?|Data-driven approach|Random|Ads only|None|A",
"Which improves user journey?|Optimization|Guesswork|Random|None|A",
"Which ensures continuous improvement?|Optimization|Ignoring results|Random|None|A",
]

DAYS_MCQ[24] = [
"What is a dashboard?|Data visualization tool|Coding tool|Email tool|CRM|A",
"Which helps track performance?|Dashboard|Guesswork|Random|None|A",
"What is KPI?|Key Performance Indicator|Key Product Info|Known Process Input|None|A",
"Which tool is used for dashboards?|Data Studio|CRM|HR tool|None|A",
"Which improves understanding?|Charts|Raw data|Random|None|A",
"Which is NOT reporting type?|Campaign report|Traffic report|Random report|Performance report|C",
"Which improves decision making?|Insights|Guesswork|Random|None|A",
"Which helps real-time tracking?|Dashboard|Static data|Random|None|A",
"Which improves client reporting?|Clear visuals|Complex data|Random|None|A",
"Which improves performance tracking?|KPIs|Guesswork|Random|None|A",
"Select all correct: Dashboard elements include|KPIs|Charts|Data sources|Random|ABC",
"Select all correct: Reporting tools include|Data Studio|Excel|Tableau|Email|ABC",
"Which improves clarity?|Visualization|Raw data|Random|None|A",
"Which ensures better results?|Data analysis|Guesswork|Random|None|A",
"Which improves ROI?|Insights|Guesswork|Random|None|A",
"Which step comes first?|Define KPIs|Build charts|Random|None|A",
"Which improves reporting quality?|Structured data|Random|Guesswork|None|A",
"Which improves long-term growth?|Analytics|Random|Ads only|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring results|Random|None|A",
"Which helps quick insights?|Dashboard|Raw data|Random|None|A",
]

DAYS_MCQ[25] = [
"What is email marketing?|Paid ads|Direct email communication|SEO|None|B",
"Which affects open rate?|Subject line|CTA|Design|None|A",
"Which improves clicks?|CTA|No content|Random|None|A",
"What is segmentation?|Divide audience|Send random emails|Ignore users|None|A",
"Which metric measures email opens?|Open rate|CTR|ROI|CPC|A",
"Which improves engagement?|Personalization|Generic emails|Random|None|A",
"Which is NOT best practice?|Spam emails|Valuable content|Clear CTA|Mobile-friendly|A",
"Which improves conversion?|Strong CTA|Weak content|Random|None|A",
"Which affects user experience?|Design|Content|Frequency|All of the above|D",
"Which improves delivery?|Clean email list|Spam|Random|None|A",
"Select all correct: Email components include|Subject line|Content|CTA|Random|ABC",
"Select all correct: Metrics include|Open rate|CTR|Conversion|Random|ABC",
"Which improves ROI?|Optimization|Guesswork|Random|None|A",
"Which ensures better results?|Data analysis|Guesswork|Random|None|A",
"Which improves campaign success?|Strategy|Random|Guesswork|None|A",
"Which step comes first?|Define audience|Send emails|Random|None|A",
"Which improves long-term growth?|Relationship building|Spam|Random|None|A",
"Which improves retention?|Engagement|Ignoring users|Random|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring|Random|None|A",
"Which improves brand trust?|Relevant emails|Spam|Random|None|A",
]

DAYS_MCQ[26] = [
"What is CRM?|Customer Relationship Management|Content Resource Management|Campaign Report Model|None|A",
"Which is CRM feature?|Lead tracking|Random data|Guesswork|None|A",
"What is automation flow?|Manual work|Automated workflow|Random task|None|B",
"Which improves lead management?|CRM|Guesswork|Random|None|A",
"Which saves time?|Automation|Manual work|Random|None|A",
"Which improves engagement?|Personalized automation|Random messages|No communication|None|A",
"Which is NOT CRM feature?|Contact management|Lead tracking|Random guessing|Automation|C",
"Which improves conversions?|Lead nurturing|Ignoring leads|Random|None|A",
"Which helps track customers?|CRM|Guesswork|Random|None|A",
"Which improves workflow?|Automation|Manual work|Random|None|A",
"Select all correct: CRM features include|Contact management|Lead tracking|Automation|Random|ABC",
"Select all correct: Automation benefits include|Time saving|Consistency|Scalability|Random|ABC",
"Which improves ROI?|Optimization|Guesswork|Random|None|A",
"Which ensures better results?|Data analysis|Guesswork|Random|None|A",
"Which improves strategy?|Insights|Guesswork|Random|None|A",
"Which step comes first?|Collect customer data|Automation|Random|None|A",
"Which improves long-term growth?|CRM strategy|Random|Ads only|None|A",
"Which improves retention?|Engagement|Ignoring users|Random|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring|Random|None|A",
"Which improves customer experience?|Personalization|Generic messages|Random|None|A",
]

DAYS_MCQ[27] = [
"What is WhatsApp marketing?|Paid ads|Messaging marketing|SEO|None|B",
"Which feature sends messages to many users?|Broadcast|Chat|Email|None|A",
"Which improves engagement?|Personalization|Spam|Random|None|A",
"What is lead nurturing?|Ignoring leads|Building relationships|Random messaging|None|B",
"Which improves conversions?|Follow-ups|No contact|Random|None|A",
"Which is NOT best practice?|Spam|Permission-based messaging|Relevant content|Timing|A",
"Which improves response rate?|Quick replies|Delay|Ignore|None|A",
"Which helps automation?|Chatbots|Manual work|Random|None|A",
"Which improves user experience?|Relevant messages|Spam|Random|None|A",
"Which helps re-engagement?|Offers|Ignore users|Random|None|A",
"Select all correct: WhatsApp features include|Broadcast|Automation|Personal messaging|Random|ABC",
"Select all correct: Nurturing methods include|Follow-ups|Offers|Updates|Random|ABC",
"Which improves ROI?|Strategy|Guesswork|Random|None|A",
"Which ensures better results?|Data analysis|Guesswork|Random|None|A",
"Which improves performance?|Optimization|Guesswork|Random|None|A",
"Which step comes first?|Get user consent|Send messages|Random|None|A",
"Which improves long-term growth?|Relationship building|Spam|Random|None|A",
"Which improves retention?|Engagement|Ignoring users|Random|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring|Random|None|A",
"Which improves brand trust?|Relevant communication|Spam|Random|None|A",
]

DAYS_MCQ[28] = [
"What is CRO?|Content writing|Conversion optimization|SEO|None|B",
"Which improves conversions?|CTA|No action|Random|None|A",
"Which tool is used for CRO?|A/B testing|CRM|HR tool|None|A",
"Which affects user experience?|Page speed|Design|Content|All of the above|D",
"Which is NOT CRO factor?|CTA|UX|Random guess|Design|C",
"Which improves ROI?|CRO|Guesswork|Random|None|A",
"Which helps testing?|A/B testing|Random|Guesswork|None|A",
"Which improves performance?|Optimization|Guesswork|Random|None|A",
"Which improves conversions directly?|Landing page|Random ads|Guesswork|None|A",
"Which improves user journey?|UX|Random|Guesswork|None|A",
"Select all correct: CRO elements include|CTA|UX|Landing page|Random|ABC",
"Select all correct: CRO tools include|A/B testing|Heatmaps|Analytics|Random|ABC",
"Which improves results?|Data analysis|Guesswork|Random|None|A",
"Which ensures better decisions?|Insights|Guesswork|Random|None|A",
"Which step comes first?|Analyze data|Run ads|Random|None|A",
"Which improves long-term growth?|Optimization|Random|Ads only|None|A",
"Which improves engagement?|UX|Random|Guesswork|None|A",
"Which improves sales?|Conversion optimization|Random|Guesswork|None|A",
"Which ensures continuous improvement?|Testing|Ignoring|Random|None|A",
"Which improves customer experience?|User-focused design|Random|Guesswork|None|A",
]

DAYS_MCQ[29] = [
"What is landing page optimization?|Random design|Improve page for conversions|Ads only|None|B",
"Which element drives action?|CTA|Logo|Footer|None|A",
"What is A/B testing?|Guesswork|Compare two versions|Random|None|B",
"Which improves conversions?|Optimization|Guesswork|Random|None|A",
"Which is NOT landing page element?|CTA|Headline|Trust signals|Random|D",
"Which improves user experience?|Fast loading|Slow page|Random|None|A",
"Which helps testing?|A/B testing|Guesswork|Random|None|A",
"Which improves trust?|Reviews|Random|Guesswork|None|A",
"Which improves performance?|Data analysis|Guesswork|Random|None|A",
"Which improves engagement?|Relevant content|Random|Guesswork|None|A",
"Select all correct: Landing page elements include|CTA|Headline|Trust signals|Random|ABC",
"Select all correct: A/B testing elements include|CTA|Images|Layout|Random|ABC",
"Which improves results?|Optimization|Guesswork|Random|None|A",
"Which ensures better decisions?|Data-driven approach|Guesswork|Random|None|A",
"Which step comes first?|Identify problem|Run ads|Random|None|A",
"Which improves long-term growth?|Optimization|Random|Ads only|None|A",
"Which improves conversions directly?|CTA|Random|Guesswork|None|A",
"Which improves user journey?|UX|Random|Guesswork|None|A",
"Which ensures continuous improvement?|Testing|Ignoring|Random|None|A",
"Which improves customer experience?|User-focused design|Random|Guesswork|None|A",
]

DAYS_MCQ[30] = [
"What is UX?|User experience|User exit|User exam|None|A",
"Which improves UX?|Easy navigation|Complex design|Slow site|None|A",
"Which metric measures conversions?|Conversion rate|Bounce rate|CTR|None|A",
"Which affects user experience?|Page speed|Design|Content|All of the above|D",
"Which is NOT UX factor?|Navigation|Design|Random guess|Speed|C",
"Which improves conversions?|Good UX|Poor design|Random|None|A",
"Which measures engagement?|Engagement rate|CPC|CPM|None|A",
"Which indicates users leaving quickly?|Bounce rate|CTR|ROI|None|A",
"Which improves performance?|Optimization|Guesswork|Random|None|A",
"Which improves user journey?|UX design|Random|Guesswork|None|A",
"Select all correct: UX elements include|Navigation|Design|Speed|Random|ABC",
"Select all correct: Conversion metrics include|Conversion rate|Bounce rate|CTR|Random|ABC",
"Which improves results?|Data analysis|Guesswork|Random|None|A",
"Which ensures better decisions?|Insights|Guesswork|Random|None|A",
"Which step comes first?|Analyze user behavior|Run ads|Random|None|A",
"Which improves long-term growth?|UX optimization|Random|Ads only|None|A",
"Which improves engagement?|Good UX|Random|Guesswork|None|A",
"Which improves sales?|Conversion optimization|Random|Guesswork|None|A",
"Which ensures continuous improvement?|Monitoring|Ignoring|Random|None|A",
"Which improves customer satisfaction?|Better UX|Random|Guesswork|None|A",
]


def main():
    root = os.path.dirname(os.path.abspath(__file__))
    out_path = os.path.join(root, "marketing_days_5_30_questions_seed.sql")
    out = io.open(out_path, "w", encoding="utf-8")
    out.write(
        "-- Marketing curriculum: Days 5-30 questions for ta_questions / ta_question_options\n"
        "-- assessment_id = day_number + 3 (Day 3=6, Day 4=7, so Day 5=8 ... Day 30=33)\n"
        "-- Ensure matching rows exist in ta_assessments before running.\n"
        "-- Day 13: 10 text (2 pts) + 20 MCQ (1 pt). Other days: 20 MCQ each.\n"
        "-- Re-running inserts duplicates — delete existing rows for that assessment_id first if needed.\n\n"
        "START TRANSACTION;\n\n"
    )

    for day in range(5, 31):
        a = aid(day)
        out.write("-- ========== Day %d (assessment_id=%d) ==========\n" % (day, a))
        out.write("SET @aid := %d;\n\n" % a)

        if day == 13:
            so = 1
            for q, kw in DAY13_TEXT:
                emit_text(out, so, q, kw)
                so += 1
            for row in DAYS_MCQ[13]:
                q, opts, mask = line_mcq(row)
                emit_mcq(out, so, q, opts, mask)
                so += 1
        else:
            if day not in DAYS_MCQ:
                raise RuntimeError("Missing MCQ data for day %d" % day)
            so = 1
            for row in DAYS_MCQ[day]:
                q, opts, mask = line_mcq(row)
                emit_mcq(out, so, q, opts, mask)
                so += 1

    out.write("COMMIT;\n")
    out.close()
    print("Wrote", out_path)


if __name__ == "__main__":
    main()
