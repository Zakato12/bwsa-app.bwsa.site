# Chapter 1 — Introduction

## 1.1 Background of the Study
Barangay water services manage household billing, payment verification, and resident records. These systems handle sensitive personal and financial information and involve multiple roles with distinct responsibilities. Without strong access boundaries, auditability, and role‑specific controls, such systems are vulnerable to unauthorized access, weak accountability, and inconsistent verification outcomes.

## 1.2 Problem Statement
Barangay water payment systems require secure, role‑scoped access control and auditability across multiple local jurisdictions. Many implementations lack enforceable boundaries between roles and barangays, resulting in risks such as data leakage, privilege misuse, and untraceable changes. This study addresses the design and evaluation of a role‑based access control (RBAC) architecture with barangay‑scoped governance and audit logging for a web‑based water billing system.

## 1.3 General Objective
Design and evaluate a secure RBAC architecture for a barangay water billing system that enforces role and barangay boundaries while supporting auditability and operational workflows.

## 1.4 Specific Objectives
1. Model the roles and permissions for Admin, Official, Treasurer, and Resident based on domain workflows.
2. Implement role‑ and barangay‑scoped access controls for user management, billing, and payment verification.
3. Implement audit logging for critical operations (user creation, billing actions, payment verification/approval).
4. Evaluate security enforcement against misuse scenarios (e.g., cross‑barangay access, role escalation).
5. Measure operational impact (e.g., access latency, workflow completion rate).

## 1.5 Scope and Limitations
- Focus is on role enforcement, data isolation, and auditability within a single application.
- External payment gateways and full compliance standards (PCI DSS) are out of scope.
- The architecture supports a hybrid decentralized model; full distributed consensus is not implemented.

## 1.6 Significance of the Study
This study provides a defensible RBAC design tailored to local governance workflows. It contributes practical guidance for securing barangay‑level billing systems and offers an evaluable framework for access control and auditability in similar public service domains.

## 1.7 Methodology (Design–Implement–Evaluate)
1. **Requirements Analysis**
   - Gather domain workflows and map tasks to roles.
   - Identify sensitive assets and access boundaries.
2. **System Design**
   - Define RBAC policies and barangay‑scoped access rules.
   - Specify audit log events and threat model.
3. **Implementation**
   - Enforce access rules via middleware and controller checks.
   - Implement audit logging for sensitive actions.
4. **Evaluation**
   - Security tests: attempt unauthorized access across barangays and roles.
   - Functional tests: validate normal workflows (billing, payment, verification).
   - Performance: measure response time before/after security enforcement.
