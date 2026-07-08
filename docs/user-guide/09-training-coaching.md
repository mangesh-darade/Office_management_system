# Module 09 — Training & Coaching

## What is this module for?

Tests, LMS courses, external training links, and coaching CRM.

![Animated overview — Training & Coaching](videos/09-training/assessment-tour-voiced.webm)


---

## Training & Assessment

**Purpose:** Create tests and review candidate submissions.

**Menu:** Training & Assessment

![Video — Training & Assessment](videos/09-training/assessment-tour-voiced.webm)


![View list — Training & Assessment](images/09-training/assessment.png)


### Add (create new)

![Add screen — Training & Assessment](images/09-training/assessment-add.png)



### Edit (update existing)

![Edit screen — Training & Assessment](images/09-training/assessment-edit.png)



### Delete / remove

**How:**

1. Delete assessment from dashboard.

---

## Master CSV import (Training + LMS + Tests)

**Purpose:** Bulk import training programs, topics, assessments, questions, and assignments from one spreadsheet.

**Menu:** Training & Assessment → Master CSV Import

**Route:** `/training/import`

**Steps:**

1. Download sample CSV (row 1 = section headers, row 2 = column names, row 3+ = data).
2. Fill one block per row (TRAINING, TOPIC, TEST, QUESTION, or ASSIGNMENT).
3. Upload CSV → Import.

**Requires:** LMS and assessment tables installed (`database/training_lms_module.sql`, `database/training_assessment_module.sql`).

---

## My Training

**Purpose:** Courses assigned to you as a learner.

**Menu:** Training → My Training

![View list — My Training](images/09-training/my-training.png)


---

## LMS Admin

**Purpose:** Create modules, topics, enrollments.

**Menu:** Training LMS Admin

![View list — LMS Admin](images/09-training/lms-admin.png)


### Add (create new)

![Add screen — LMS Admin](images/09-training/lms-module-add.png)



### Delete / remove

**How:**

1. Delete module or topic from admin lists.

---

## External training

**Purpose:** Track third-party video or URL courses.

**Menu:** External Training

![View list — External training](images/09-training/external-training.png)


### Add (create new)

![Add screen — External training](images/09-training/external-training-add.png)



### Edit (update existing)

![Edit screen — External training](images/09-training/external-training-edit.png)



### Delete / remove

**How:**

1. Delete from external training list.

---

## Coaching

**Purpose:** Coaching business: clients, sessions, billing.

**Menu:** Coaching

![View list — Coaching](images/09-training/coaching.png)


### Add (create new)

**Steps:**

1. Use Coaching → Sessions → Create.
2. Clients and billing in sub-menus.

---

**Next module:** [10 — Administration](10-administration.md)
