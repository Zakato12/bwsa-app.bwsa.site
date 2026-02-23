# Chapter 1 - Introduction

## 1.1 Background of the Study
Barangay water services manage household billing, payment verification, and resident records. These systems handle sensitive personal and financial information and involve multiple roles with distinct responsibilities. Without strong access boundaries, auditability, and role-specific controls, such systems are vulnerable to unauthorized access, weak accountability, and inconsistent verification outcomes.

## 1.2 Problem Statement
Barangay water payment systems require secure, role-scoped access control and auditability across multiple local jurisdictions. Many implementations lack enforceable boundaries between roles and barangays, resulting in risks such as data leakage, privilege misuse, and untraceable changes. This study addresses the design and evaluation of a role-based access control (RBAC) architecture with barangay-scoped governance and audit logging for a web-based water billing system.

## 1.3 General Objective
Design, implement, and evaluate an RBAC-enabled secure web architecture for decentralized barangay water utility payments with a GCash verification workflow, enforcing role and barangay boundaries while maintaining auditability and operational continuity.

## 1.4 Specific Objectives
1. Model the roles and permissions for Admin, Official, Treasurer, and Resident based on domain workflows.
2. Implement role- and barangay-scoped access controls for user management, billing, and payment verification.
3. Implement a GCash payment verification workflow with OCR-assisted receipt extraction and manual fallback validation.
4. Implement audit logging for critical operations (user creation, billing actions, payment verification and approval).
5. Evaluate security enforcement against misuse scenarios such as cross-barangay access and role escalation.
6. Measure operational impact, including access latency, workflow completion rate, and verification turnaround time.

## 1.5 Scope and Limitations
- Focus is on role enforcement, data isolation, and auditability within a single application.
- External payment gateways and full compliance standards (for example PCI DSS) are out of scope.
- The architecture supports a hybrid decentralized model; full distributed consensus is not implemented.

## 1.6 Significance of the Study
This study provides a defensible RBAC design tailored to local governance workflows. It contributes practical guidance for securing barangay-level billing systems and offers an evaluable framework for access control and auditability in similar public service domains.

## 1.7 Methodology (Design-Implement-Evaluate)
1. **Requirements Analysis**
   - Gather domain workflows and map tasks to roles.
   - Identify sensitive assets and access boundaries.
2. **System Design**
   - Define RBAC policies and barangay-scoped access rules.
   - Specify audit log events and threat model.
3. **Implementation**
   - Enforce access rules via middleware and controller checks.
   - Implement audit logging for sensitive actions.
4. **Evaluation**
   - Security tests: attempt unauthorized access across barangays and roles.
   - Functional tests: validate normal workflows (billing, payment, verification).
   - Performance tests: measure response time before and after security enforcement.

## 1.8 Defense Talking Points: OCR Limitation and Future Improvements
### Why the System Is Defensible Without OCR
- OCR is implemented as an enhancement, not a hard dependency.
- Core workflows are fully operational without OCR:
  - bill generation,
  - resident payment submission,
  - treasurer verification and approval,
  - reporting.
- If OCR is unavailable, the system falls back to manual receipt validation by authorized personnel, preserving service continuity and control.

### Current Limitation
- OCR accuracy may vary due to image quality, lighting, and receipt format differences.
- OCR output is assistive data only and is not treated as final authority for approval decisions.

### Future Improvements
1. Improve OCR reliability through better image preprocessing and model tuning.
2. Add confidence thresholds and stricter exception handling for low-confidence extraction.
3. Expand support for additional receipt formats and payment providers.
4. Optimize asynchronous OCR processing for faster and more scalable verification.

### Suggested Q and A Response
If asked, "Why include OCR if the system works without it?"
- OCR reduces verification time and clerical workload.
- The system is intentionally designed so governance, correctness, and approvals do not depend on OCR availability.

## 1.9 Expected Results/Outputs
1. A working web-based BWASA management system with secure login, role-based access, and barangay-scoped data boundaries.
2. Functional modules for resident records, bill generation, payment submission, verification and approval, and reporting.
3. A GCash receipt verification process using OCR-assisted extraction with manual verification fallback for reliability.
4. Security controls that prevent unauthorized role actions and cross-barangay data access.
5. Audit logs that provide traceable records of critical user and payment-related actions.
6. Automated and functional test results demonstrating that core workflows and security controls operate as designed.
