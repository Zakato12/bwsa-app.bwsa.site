# An RBAC-Enabled Secure Web Architecture for Decentralized Utility Payment Systems with GCash Verification Workflow

## Abstract
The management of decentralized utility systems is often hampered by insecure data handling and manual verification processes prone to human error and fraud. This paper presents an RBAC-enabled secure web architecture designed to provide a robust computational framework for decentralized administrative units. Unlike traditional billing platforms that rely on subjective human oversight, this architecture enforces a strict Role-Based Access Control (RBAC) model to ensure accountability and data integrity.
Central to the architecture is a multi-tier security design that maintains strict multi-tenant data isolation across different barangays. This isolation is technically enforced through shared-database row-level filtering, where every database query is programmatically scoped by a unique barangay_id via Laravel Global Scopes. The system integrates a deterministic verification engine powered by Optical Character Recognition (OCR), which models transaction legitimacy as a structural validation problem. By utilizing a rule-based algorithm, the architecture automatically extracts and evaluates transaction data specifically GCash reference numbers and amounts against formal constraints including resident status and billing accuracy.
Experimental results show that this approach reduces processing latency by over 92%, cutting the time from 180 seconds to under 10 seconds. The system achieved a 92% accuracy rate, with a Precision of 94.25% and a Recall of 96.47%, providing a scalable solution for operational transparency.
Keywords: RBAC, Secure Web Architecture, Multi-tenant Data Isolation, Computational Verification Framework, Rule-Based Algorithm, Decentralized Utility Systems, GCash Transaction Validation, Operational Transparency.

## 1. INTRODUCTION

### 1.1 Background of the Study
Document and transaction verification is a well-studied problem in computer science involving the automated assessment of data integrity and structural correctness. In decentralized utility environments like the Barangay Water System Association (BWSA), financial reconciliation requires the submission and validation of digital payment receipts. These transactions are commonly verified through manual inspection, which introduces subjectivity, inconsistency, and significant processing delays for over 500 serviced households [1], [3], [4].
Most existing utility billing platforms emphasize simple workflow automation while retaining human-dependent verification. From a computational perspective, this approach fails to address the underlying challenges of data isolation and multi-user authorization across different administrative units. To address this gap, the validation process can be redefined as a systematic evaluation. Instead of manual checks, each digital receipt is screened against structured system rules specifically member eligibility, billing accuracy, and transaction reference consistency ensuring that only verified data is recorded [1], [5].
This study proposes an RBAC-enabled web architecture that replaces subjective inspection with automated, rule-based verification. By integrating an Optical Character Recognition (OCR) validation workflow with a strict Role-Based Access Control (RBAC) model, the system logically enforces data isolation in a multi-tenant environment. While this architecture utilizes deterministic rule-based checks rather than formally proven mathematical models, it significantly improves transaction efficiency, reduces verification latency by over 90%, and provides a structured, scalable solution for financial data reconciliation in decentralized administrative contexts [1], [3], [4], [6].
### 1.2 Statement of the Problem
General Problem:
How can a secure, RBAC enabled web architecture and computational verification framework be designed to ensure the integrity, security, and efficiency of transaction processing in decentralized utility payment systems?
Specific Problems:
This study addresses several key challenges related to the implementation of a secure decentralized utility payment system. It examines how an RBAC-enabled secure web architecture can be implemented to enforce strict authorization and accountability among multiple administrative roles. The study also investigates how the system can ensure data isolation and integrity in a multi-tenant environment to prevent unauthorized access between different administrative units or barangays. Furthermore, it explores how a rule-based verification algorithm can be formulated to replace manual and subjective inspection with deterministic validation of transaction data. In addition, the research analyzes how a formal constraint-based model can be utilized to identify and flag inconsistent, duplicate, or tampered payment records. Finally, the study evaluates how the proposed architecture can demonstrate significant improvements in verification latency, computational correctness, and overall security robustness [7], [8], [9].

### 1.3 Research objective
General Objective:
This study aims to achieve several specific objectives. First, it seeks to formulate a secure and auditable Role-Based Access Control (RBAC) model tailored for decentralized utility payment environments with multiple administrative roles. Second, it aims to design and test a multi-tenant database partitioning strategy that ensures data isolation, integrity, and scalability across multiple administrative units or barangays. Third, the study intends to formulate and implement a rule-based verification algorithm that evaluates the consistency of transaction data against system-generated records using deterministic constraints. Fourth, it aims to design a multi-tier secure web architecture that integrates algorithmic validation with standardized data workflows to ensure transaction reliability. Finally, the study evaluates the proposed system architecture in terms of computational correctness, security robustness, and verification accuracy [1], [5].
Section 1.4 (Scope and Limitations)
The primary focus of this study is to develop a secure web architecture for decentralized utility payment systems, specifically targeting the Barangay Water System Association (BWSA) workflow. The system's core functionality includes automated billing, role-based access for treasurers and residents, and a rule-based verification engine for digital receipts.
However, the study is subject to several limitations related to the system’s scope and technical implementation. First, the system’s automated verification process is highly dependent on the quality and format of the uploaded GCash receipt image. Factors such as low resolution, excessive blur, or poor lighting conditions may negatively affect the accuracy of Optical Character Recognition (OCR) processing. Second, the system is designed specifically for the standard GCash receipt layout available at the time of development; therefore, any significant changes in the GCash interface or receipt format may require updates to the OCR parsing templates. Third, although the system performs structural and reference consistency checks, sophisticated digital edits or high-quality image tampering may still bypass automated validation and require manual verification by the treasurer. Fourth, the system does not directly integrate with the GCash API for financial processing and instead relies on algorithmic validation of submitted transaction metadata and OCR results. Fifth, the system is limited to web-based access and does not include a dedicated mobile application. Sixth, the system’s notification functionality is limited to email alerts and dashboard updates and does not currently support SMS or push notification services. Finally, the operational efficiency of the system depends on a stable internet connection, since server-side OCR processing and database access require continuous connectivity. Additionally, although OCR automates data extraction, human intervention by the treasurer is still required for transactions flagged by the algorithm due to detected discrepancies [3], [4], [10].
### 1.5 Significance of the Study
This study provides several benefits to different stakeholders involved in decentralized utility management. First, it benefits BWSA residents by providing a transparent and verifiable platform for submitting digital payment receipts and tracking their transaction history. Second, the system assists BWSA treasurers by facilitating efficient verification of payments through an algorithmic framework that reduces manual workload and minimizes human error. Third, the study supports barangay officials by simplifying the oversight of decentralized utility operations and promoting financial accountability across different administrative units. Fourth, the research benefits the researchers themselves by enhancing their expertise in implementing role-based security models, multi-tenant database architectures, and constraint-based algorithms. Fifth, the proposed system offers system developers a technical blueprint for implementing RBAC-enabled architectures and constraint-based algorithms in localized multi-tenant utility environments. Finally, the study contributes to the technical community by demonstrating a secure RBAC-enabled web architecture that can serve as a model for integrating automated validation mechanisms in decentralized utility systems [1], [5], [9], [10].
### 1.6 Definition of Terms
Role-Based Access Control (RBAC) refers to a security model that restricts system access to authorized users based on their specific roles, such as Member, Treasurer, or Admin [1], [2].
Constraint-Based Validation refers to a process where a transaction must satisfy a set of formal rules, including member status and billing accuracy, before being classified as valid [3], [4].
Multi-Tenancy refers to a software architecture where a single instance of the system serves multiple distinct administrative units or barangays while ensuring strict data isolation [5].
GCash Verification Workflow refers to a step-by-step computational process for validating digital payment metadata and reference numbers submitted by users to ensure financial accountability [3], [11].
Verification Latency refers to the total time required by the algorithm to process and validate a transaction, which the system reduces to approximately 5–10 seconds. [8]
Data Isolation refers to a security measure within the database design that prevents unauthorized cross-access of information between different organizational entities or barangays [5], [8].
## 2. RELATED SYSTEM

### 2.1 Online Utility Billing Systems: Enhancing Accuracy and Efficiency
This study addresses the computational problem of data inconsistency and manual entry errors in community-based utility organizations. The research presents an automated billing framework that replaces traditional paper-ledgers with a centralized database to ensure data integrity. From a computational perspective, the solution involves the implementation of automated calculation logic to manage water consumption rates and billing cycles. By digitizing the record-keeping process, the system solves the problem of inefficient auditing and provides a scalable architecture for managing financial data in localized settings [11].
### 2.2 Web-Based Financial Management Systems and Role-Based Access Control
This study explores the integration of Role-Based Access Control (RBAC) as a solution to the security problem of unauthorized data manipulation in web-based financial platforms. The computational challenge addressed is the enforcement of strict access policies across different user levels Administrators, Treasurers, and Members. The study models the system architecture to ensure that each role can only interact with specific database objects, thereby maintaining data confidentiality and administrative accountability. The results indicate that an RBAC-enabled architecture significantly reduces the risk of internal data breaches in decentralized financial environments [1], [2], [12].
### 2.3 Digital Payment and Deposit System for Community Cooperatives
This research focuses on the problem of reconciling offline payments with digital records in community cooperatives. The computational solution presented is a digital deposit tracking system that automates the logging of member contributions. By implementing a centralized repository for payment data, the study addresses the problem of fragmented financial records and delayed reconciliation. The system logic is designed to handle transaction state transitions, ensuring that every payment is accurately mapped to a specific member account, which improves the overall transparency and auditability of the cooperative’s financial workflow. [1]
### 2.4 Modern Community Systems Integrating Digital Payments and Verification Workflows
This study presents a computational framework for integrating third-party digital payment metadata into localized administrative systems. The primary problem addressed is the lack of automated verification for mobile wallet transactions, which leads to manual verification bottlenecks. The solution involves a rule-based verification workflow that checks transaction reference numbers against historical data to prevent duplicate entries. The research demonstrates how algorithmic validation can reduce the time spent on manual oversight while increasing the accuracy of financial reconciliation in community-managed utility systems [3], [4].
### 2.5 Constraint-Based Logic for Automated Transaction Validation
This study introduces a deterministic approach to solving the problem of transaction authenticity in web-based billing platforms. The research frames the verification of digital receipts as a structural validation problem, where each transaction must satisfy a set of formal constraints before being committed to the ledger. Developed using an MVC framework, the system implements a decision-making algorithm that evaluates metadata consistency. This computational approach provides a faster and more reliable alternative to subjective human inspection, ensuring that only verified and unique transactions are processed by the system [3], [4], [12].

## 3. METHODOLOGY

### 3.1 Research Design
This study adopts an experimental algorithmic research design focusing on algorithm formulation, implementation, and evaluation. The design emphasizes the development of a secure, RBAC-enabled web architecture and a constraint-based verification algorithm to solve the problems of manual oversight and data integrity in decentralized utility systems. The research follows a structured approach from the design of role-based security models to the empirical testing of verification accuracy and latency ensuring that the proposed computational framework effectively replaces subjective inspection with deterministic validation rules [1], [3], [4].

### 3.2 Computational Problem Definition
Given a set of submitted transaction metadata and digital payment records, the problem is to determine transaction validity using constraint-based rules and produce deterministic classification outcomes. The system must evaluate each transaction against a formal set of constraints including member authorization, billing consistency, and reference number uniqueness-to classify it as Valid, Invalid, or Flagged for review. This computational approach ensures that the integrity of the financial ledger is maintained through an automated, rule-based decision process rather than subjective manual inspection.

### 3.3 Algorithmic Approach
The algorithm performs file constraint verification, structural integrity analysis, content consistency evaluation, and decision classification, integrated with an automated image-to-data extraction workflow. Specifically, the process begins by verifying the metadata constraints of the submitted payment record to ensure it matches the authorized role, such as a Resident initiating a transaction within their assigned barangay scope. It then conducts a structural integrity analysis to check for duplicate GCash Reference Numbers within the database to prevent double-entry fraud. The content consistency evaluation utilizes Optical Character Recognition (OCR) to automatically extract the amount and reference number from the uploaded receipt, comparing these values against the user’s input and the current billing balance. Finally, this leads to a decision classification where the transaction is either queued for Treasurer review or rejected based on deterministic logic, ensuring that only verified data is committed to the decentralized ledger.

### 3.4 Architectural Implementation and Data Environment
The dataset consists of transaction metadata and digital payment records collected from the Barangay Water System Association (BWSA) during a specific billing period. To evaluate the proposed system, the secure web architecture is configured within a multi-tenant database environment where data isolation is strictly enforced. The deterministic outcomes of the constraint-based algorithm are compared against existing manual ledger records to establish a baseline for verification accuracy and computational reliability.

### 3.5 Evaluation Metrics
The performance of the proposed system was evaluated using several key metrics related to verification efficiency and accuracy. First, verification time refers to the total duration required by the algorithm to process and validate submitted transaction metadata, measured from the moment the receipt is uploaded until the final transaction classification is produced. Second, the False Acceptance Rate (FAR) measures the frequency at which the system incorrectly classifies an invalid or fraudulent transaction record as valid. Third, the False Rejection Rate (FRR) represents the ratio at which the system incorrectly rejects a legitimate payment record as invalid due to strict constraint mismatches. Fourth, manual review reduction refers to the percentage decrease in the manual workload of BWSA officials achieved by automating the verification process compared with traditional manual inspection methods. Finally, validation accuracy measures the overall precision of the system in correctly identifying both valid and invalid transactions based on a ground-truth dataset of billing records.

### 3.3 Development Tools
### 3.3.1 Visual Studio Code
Visual Studio Code served as the primary Integrated Development Environment (IDE) for constructing the secure web architecture. It provided the necessary environment for implementing the complex Role-Based Access Control (RBAC) logic and debugging the deterministic verification algorithm, ensuring high code quality and structural integrity.

### 3.3.2 PHP (Hypertext Preprocessor)/ Laravel Framework
The system was developed using PHP through the Laravel Framework. Laravel served as the primary backend engine, providing a robust structure for implementing the Role-Based Access Control (RBAC) through its built-in Middleware and Policy features. It ensured secure data handling, managed the multi-tenant database partitioning logic, and executed the deterministic verification algorithm with high efficiency and security.

### 3.3.3 HTML (Hypertext Markup Language)
HTML was utilized to architect the structural layout of the web interface. It defined the data presentation layers where administrators and members interact with billing records and transaction status dashboards.

### 3.3.4 CSS (Cascading Style Sheets)Bootstrap
CSS, integrated with the Bootstrap framework, was employed to manage the visual representation and responsive design of the system. This combination ensured that the complex output of the verification algorithm and administrative data were presented in a clear, scannable format across different devices while maintaining a consistent UI through Bootstrap’s structural grid system.
### 3.3.5 JavaScript
JavaScript provided the client-side logic necessary for a responsive user experience. It handled real-time input validation for transaction metadata and managed asynchronous communication with the server to ensure the system remained interactive during the verification process.

### 3.3.6 MySQL Database
MySQL served as the relational database management system for the multi-tenant architecture. It was designed to maintain strict data isolation, storing member records, billing histories, and verification logs while ensuring fast query execution for the rule-based engine.
### 3.3.7 Hostinger
Hostinger provided the cloud-based server environment for the deployment of the secure web architecture. It ensured the system's availability and provided the necessary PHP and MySQL environment to execute the algorithmic verification workflows in a live web setting.

### 3.4 Conceptual Framework (System Architecture)
The system architecture of the BWSA Automated Billing and Receipt Verification System describes how its main components work together to support a secure and deterministic validation process. It illustrates how various user roles Residents, Treasurers, Officials, and System Admins access the system through an RBAC-protected web interface, and how the backend logic enforces multi-tenant data isolation at the Barangay level. The architecture details include the OCR-integrated verification engine that processes transaction metadata and receipt images against the MySQL database to produce deterministic outcomes. Furthermore, the hosting environment ensures the system's availability and facilitates the secure flow of information between the front-end interface, the server-side OCR processing logic, and the relational database management system.
### 3.5 Multi-Tenancy & Security
The system implements a Shared Database, Shared Schema multi-tenancy model to ensure data integrity across multiple barangays. To prevent unauthorized cross-tenant access, the architecture utilizes Laravel Global Scopes. Every database query is automatically appended with a WHERE barangay_id = ? constraint based on the authenticated user's session. This ensures that a Treasurer from Barangay A cannot view or manipulate records from Barangay B, even if they attempt to guess record IDs [5], [8].
To further strengthen the security framework, several threat mitigation mechanisms are implemented. First, SQL injection attacks are prevented through PDO parameter binding enforced in the Eloquent ORM, ensuring that all database queries are properly sanitized. Second, Cross-Site Request Forgery (CSRF) protection is applied by validating all state-changing requests, such as payment submissions, through cryptographic security tokens. Finally, credential security is maintained by encrypting user passwords using the Bcrypt hashing algorithm, which ensures that authentication data remains protected even if database exposure occurs [7], [10], [13].
Figure 3.1 System Architecture
Figure 3.1 shows the architecture of the BWSA Automated Billing and Receipt VerificationSystem
Users interact with the system through the frontend, which is designed using HTML, CSS, JavaScript, and Bootstrap. The frontend sends HTTP requests to the backend, where PHP handles data processing, receipt validation logic, and automated billing calculations. The backend communicates with the MySQL database through read and write operations to store and retrieve member records, billing history, and verified payment data.
The system is deployed on Hostinger, which manages web access and ensures that the application remains available to barangay members and administrators. Hostinger serves as the hosting environment that delivers the frontend to users and routes backend requests for database operations. Through continuous interaction between the frontend, backend, hosting server, and database, the system provides reliable payment verification, real-time billing updates, and efficient management of water association records.

## 4. ALGORITHM DESIGN AND ANALYSIS

### 4.1 Formal Constraint Definitions
Let a transaction T  be represented as a tuple:

where:
### 4.1.1 Member Constraint
The Member Constraint ensures that the requesting user exists in the system and has an active account status. Transactions initiated by deactivated or unregistered members are automatically rejected to prevent invalid participation in the billing and payment workflow.
### 4.1.2 Billing Constraint
The Billing Constraint verifies that the billed amount corresponds to the assigned monthly fee and that penalty values, when applicable, are correctly computed for the selected billing period. This constraint prevents duplicate, inconsistent, or incorrect billing records.
### 4.1.3 Payment Constraint
The Payment Constraint validates that the submitted payment amount matches the billed amount and that the selected payment method is supported by the system. This constraint prevents underpayment, overpayment, and the use of unsupported payment channels.
### 4.1.4 Role-Based Access Constraint
The Role-Based Access Constraint confirms that the requesting user is authorized to perform the intended action based on the assigned system role. This constraint enforces secure access control for all sensitive operations.
### 4.1.5 GCash Verification Constraint
The GCash Verification Constraint ensures that a valid GCash reference number or receipt image is provided for every online transaction. It also checks the uniqueness of the reference number to prevent duplicate submissions and enables the system to flag unverified transactions for manual review.
A transaction is classified using the decision function D(T), which is defined as

Table 4.1. Validation Constraints Used in the Proposed Algorithm

Figure 4.2 Algorithm Decision Tree
Figure 4.2 represents the step-by-step process for validating member transactions, enforcing RBAC, and flagging payments for review. The decision logic begins when a user submits transaction metadata, which immediately undergoes a Role-Based Access Constraint check to determine the level of authorization for the request. This ensures that the transaction originates from a valid administrative unit or barangay before any sensitive data is processed.
Once authorized, the system performs a Member Constraint check to verify the identity and status of the sender. The process then branches into the Billing and Payment Constraints, where the system mathematically compares the paid amount against the recorded balance due.
For online transactions, the GCash Verification Constraint acts as a final gateway, ensuring that reference numbers are unique and that supporting digital receipts are present. If any step in this tree returns a negative result, the transaction is automatically flagged for manual review or rejected; otherwise, it is committed to the database as a "Valid" record.

### 4.3 Constraint-Based Validation Algorithm
The constraint-based validation algorithm serves as the core decision mechanism of the proposed architecture by integrating role-based access control with automated payment verification. The algorithm follows a strict security-first execution model in which all transactional data are processed only after the user’s authorization and tenant scope have been successfully verified.
The algorithm receives four inputs, namely the user credentials (Cr), the member profile (Cm), the transaction data (T), and the uploaded receipt image (I). Its output is a deterministic classification of the transaction as Authorized and Validated, Access Denied, or Flagged for Review.
The validation process begins with the enforcement of the role-based security layer. The system first verifies whether the role contained in the user credentials belongs to the authorized role set, which includes Resident, Treasurer, and Administrator. If the role is not authorized, the transaction is immediately rejected. The algorithm then verifies the membership status of the requesting user and denies the request if the account is inactive. Finally, the system enforces multi-tenant isolation by comparing the barangay identifier associated with the user credentials and the transaction record. If the identifiers do not match, the transaction is rejected to prevent cross-tenant data access.
After the successful completion of all role and tenant checks, the algorithm proceeds to the data integrity and receipt verification stage. At this stage, the system extracts the transaction metadata from the uploaded receipt image using an optical character recognition process [3], [4]. The extracted reference number is then compared against existing records in the database to detect duplicate transactions. If the reference number already exists, the transaction is classified as invalid. The algorithm subsequently evaluates the extracted payment amount against the billed amount recorded in the transaction data. If the submitted amount is less than the required billed amount, the transaction is flagged for review due to potential underpayment. In addition, if the reference number cannot be reliably extracted or is missing from the receipt image, the transaction is flagged and marked as requiring proof of payment.
Only transactions that successfully satisfy all security, integrity, and verification constraints are allowed to proceed to the final commitment stage. In this stage, the validated transaction is permanently recorded in the secure ledger, and the corresponding member balance is updated. The algorithm then returns a successful validation outcome, indicating that the transaction has been authorized and verified according to the defined constraint model.Pseudo CodeAlgorithm ExecuteSecureTransaction(Cr, Cm, T, I)
Input:
Cr – User credentials
Cm – Member profile
T  – Transaction data
I  – Receipt image

Output:
Authorized and Validated, Access Denied, or Flagged for Review

Begin

If Cr.role is not in {Resident, Treasurer, Admin} then
return "Access Denied: Unauthorized Role"
End If

If Cm.status = "Inactive" then
return "Access Denied: Account Suspended"
End If

If Cr.barangayID ≠ T.barangayID then
return "Access Denied: Multi-tenant Isolation Breach"
End If

Data_OCR ← ExtractReceiptMetadata(I)

If Data_OCR.ReferenceID exists in the database then
return "Invalid: Duplicate Transaction Detected"
End If

If Data_OCR.Amount < T.BilledAmount then
return "Flagged: Underpayment Detected"
End If

If Data_OCR.ReferenceID is NULL or unreadable then
return "Flagged: Proof of Payment Required"
End If

Commit transaction to secure ledger

Update member balance in Cm

return "Transaction Successfully Validated"

End
### 4.4 Formal Constraint Definitions (Pseudo-Mathematical Notation)
Let a transaction T be represented as a tuple
T=(M,B,P,R,V),
where M denotes the member information, B denotes the billing information, P denotes the payment information, R denotes the role and authorization attributes, and V denotes the verification input derived from the submitted GCash receipt.
Let the set of constraints be defined as

The Member Constraint is defined as

The Billing Constraint is defined as

The Payment Constraint is defined as

The Role Constraint is defined as

The GCash Constraint is defined as

The decision function of the proposed validation model is defined as

A transaction is considered valid if and only if all constraints evaluate to one.
### 4.5 Complexity Analysis
The billing and payment verification algorithm is designed to ensure computational efficiency even as the dataset of the Water Association grows. The system operates in O(m + t + r) time, where:

Table 4.5. Time complexity analysis of each validation stage.
In this analysis, m represents the number of member records processed during the identification phase, t represents the number of transaction records examined to verify the uniqueness of reference numbers, and r represents the number of role-based access checks performed to ensure secure execution.
The constant-time performance of the authorization, member lookup, and duplicate detection stages is achieved through indexed database queries and middleware-based access validation. The dominant computational cost is primarily associated with receipt image processing and report aggregation, while the overall end-to-end validation process remains linear with respect to the number of members, transactions, and access checks.
5. RESULTS AND DISCUSSION
This chapter presents the findings and analysis of the data gathered from the testing of the RBAC-Enabled Secure Web Architecture. The evaluation focuses on the accuracy of the verification engine, the efficiency of the workflow compared to manual methods, the results of the user acceptance testing, and a comprehensive discussion of the system's performance.
### 5.1 Verification Accuracy (Confusion Matrix)
The system’s deterministic algorithm was tested using 100 transactions, consisting of both valid and invalid payment submissions, to evaluate its computational correctness. The results were compared against manual verification to establish a baseline for accuracy.
Table 5.1. Confusion Matrix of the Verification Algorithm (n=100)

In this evaluation, a true positive (TP) represents a valid payment correctly accepted by the system, a false positive (FP) represents an invalid or fraudulent payment incorrectly accepted by the system, a true negative (TN) represents an invalid payment correctly rejected, and a false negative (FN) represents a valid payment incorrectly rejected.
Based on the results shown in Table 5.1, the overall validation accuracy of the proposed algorithm is computed as
Hence, the system achieved an overall verification accuracy of 92%. This result indicates that the proposed constraint-based algorithm is able to correctly identify legitimate transactions while minimizing the acceptance of fraudulent or invalid submissions.
Beyond overall accuracy, additional performance metrics were computed to further evaluate the reliability of the classification results. The precision of the system was recorded at 94.25%, indicating a high level of reliability for transactions labeled as valid by the algorithm. The recall rate reached 96.47%, demonstrating the system’s strong capability to correctly capture legitimate payment records. The resulting F1-score was 95.35%, which reflects a balanced trade-off between precision and recall.
An analysis of the classification errors shows that the five false positive cases were primarily caused by optical character recognition misinterpretations, such as incorrectly recognizing a digit zero as the digit eight in low-quality receipt screenshots. The three false negative cases occurred when the confidence threshold of the optical character recognition process was not satisfied due to excessive image blur, which led the system to conservatively flag the affected transactions for manual review by the treasurer.
### 5.2 Verification Time Comparison
The operational efficiency of the proposed system was evaluated by measuring the time required to verify a single transaction using the traditional manual procedure and the automated verification engine. The recorded results represent the average processing time per submitted payment record.
Table 5.2. Average verification time per document
The experimental data shows that the system is approximately 37.5 times faster than the manual process. Manual verification typically takes 2 to 3 minutes because officers must manually cross-reference the GCash application, check member history, and record data. In contrast, the proposed architecture automates this through a database query and OCR-driven data extraction, reducing processing latency by over 92%.
### 5.3 Discussion
The experimental results validate the effectiveness of the proposed RBAC-enabled secure web architecture in addressing the challenges associated with manual verification and insecure data handling in decentralized utility payment systems. By replacing subjective human inspection with a deterministic, constraint-based verification algorithm, the system significantly improves the reliability and consistency of transaction validation.
The integration of a multi-tenant data isolation mechanism ensures that financial records belonging to different barangays remain logically separated, thereby preventing unauthorized cross-tenant access and reducing the risk of data leakage. This architectural design strengthens the overall security posture of the system while maintaining operational flexibility for decentralized administrative units.
Furthermore, the achieved verification accuracy of 92% and the observed reduction in processing time of approximately 97% demonstrate that the proposed approach offers a scalable and efficient solution for transaction verification. The performance gains indicate that the system is capable of handling increasing transaction volumes without sacrificing correctness or responsiveness. In addition, the results of the role-based access control evaluation confirm that the proposed RBAC model enforces strict authorization policies, ensuring that only authorized personnel are permitted to access sensitive operations.
6. CONCLUSION AND FUTURE WORK
The development of the RBAC-enabled secure web architecture demonstrates a substantial improvement over traditional manual utility payment workflows. Based on the experimental results, the study confirms that the proposed system significantly enhances operational efficiency, validation reliability, and security enforcement within decentralized utility payment environments. In particular, the system successfully reduced the average verification time from approximately 150 seconds under the manual process to only 5–10 seconds using automated verification, representing a considerable improvement in processing speed. The verification engine achieved an overall accuracy rate of 92%, indicating that the proposed constraint-based algorithm is effective in identifying legitimate transactions while appropriately flagging potential duplicate or fraudulent reference numbers. Furthermore, the implementation of role-based access control ensured that only authorized users are permitted to access and manage sensitive financial data, thereby satisfying the security requirements of decentralized administrative operations.
To further enhance the functionality and adaptability of the proposed system, several directions for future work are recommended. First, an SMS notification mechanism may be integrated to deliver real-time payment status updates and billing reminders to residents, enabling timely communication even for users with limited internet connectivity. Second, the development of a dedicated mobile application for Android and iOS platforms may be pursued to support offline data entry and push notification services, thereby improving usability and accessibility beyond the current web-based interface. Finally, direct integration with the official GCash merchant application programming interface may be implemented to enable fully automated payment confirmation, eliminating the need for users to upload receipt images and further strengthening the reliability and efficiency of the verification workflow.
## REFERENCES
[1] Vinay Reddy Male. 2025. Decoding Role-Based Access Control (RBAC): A Comprehensive Analysis of Role Assignment and Permission Mapping in Modern Enterprise Systems. International Journal of Scientific Research in Computer Science, Engineering and Information Technology (IJSRCSEIT).

[2] Mariya Penelova. 2021. Access Control Models. Cybernetics and Information Technologies 21, 4 (2021), 77-104. https://doi.org/10.2478/cait-2021-0044.

[3] Okechukwu O., George O., and Isaac O. N. 2024. Enhanced Text Recognition in Images Using Tesseract OCR within the Laravel Framework. Asian Journal of Research in Computer Science 17, 9 (2024), 58-69. https://doi.org/10.9734/AJRCOS/2024/V17I9499.

[4] Minghao Li, Tengchao Lv, Jingye Chen, Lei Cui, Yijuan Lu, Dinei A. F. Florencio, Cha Zhang, Zhoujun Li, and Furu Wei. 2023. TrOCR: Transformer-Based Optical Character Recognition with Pre-trained Models. In Proceedings of the AAAI Conference on Artificial Intelligence, Vol. 37, No. 11. 13094-13102. https://doi.org/10.1609/aaai.v37i11.26538.

[5] Hewa Majeed Zangana, Ayaz Khalid Mohammed, and Subhi R. M. Zeebaree. 2024. Systematic Review of Decentralized and Collaborative Computing Models in Cloud Architectures for Distributed Edge Computing. Sistemasi: Jurnal Sistem Informasi 13, 4 (2024), 1501-1509. https://doi.org/10.32520/stmsi.v13i4.4169.

[6] European Union Agency for Cybersecurity (ENISA). 2023. ENISA Threat Landscape 2023. https://www.enisa.europa.eu/publications/enisa-threat-landscape-2023.

[7] OWASP Foundation. 2021. OWASP Top 10: The Ten Most Critical Web Application Security Risks. https://owasp.org/Top10/.

[8] ISO/IEC. 2022. ISO/IEC 27001:2022 Information Security, Cybersecurity and Privacy Protection - Information Security Management Systems - Requirements. International Organization for Standardization.

[9] Verizon. 2024. 2024 Data Breach Investigations Report (DBIR). https://www.verizon.com/business/resources/reports/dbir/.

[10] Andrew Hoffman. 2024. Web Application Security: Exploitation and Countermeasures for Modern Web Applications, 2nd ed. O'Reilly Media, Inc.

[11] SciVerse ScienceDirect. 2025. Article Page with PII: S2214212625000353. https://www.sciencedirect.com/science/article/pii/S2214212625000353.

[12] Sebastian Groll, Ludwig Fuchs, and Gunther Pernul. 2025. Separation of Duty in Information Security. ACM Computing Surveys. https://doi.org/10.1145/3715959.

[13] Petar Zlatarov and Galya Ivanova. 2025. Improving Cybersecurity Education with a Framework for Session Management Vulnerabilities. In 2025 10th International Conference on Energy Efficiency and Agricultural Engineering (EE&AE). IEEE.
