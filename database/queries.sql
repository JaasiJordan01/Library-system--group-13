-- 1. Books currently on loan with member name and due date
SELECT b.title, m.full_name, l.date_issued, l.due_date
FROM loans l
JOIN books b ON l.book_id = b.book_id
JOIN members m ON l.member_id = m.member_id
WHERE l.status IN ('borrowed','overdue')
ORDER BY l.due_date;

-- 2. Overdue loans with days overdue
SELECT b.title, m.full_name, l.due_date,
       DATEDIFF(CURDATE(), l.due_date) AS days_overdue
FROM loans l
JOIN books b ON l.book_id = b.book_id
JOIN members m ON l.member_id = m.member_id
WHERE l.status = 'overdue'
ORDER BY days_overdue DESC;

-- 3. Number of loans per category
SELECT c.name AS category, COUNT(l.loan_id) AS total_loans
FROM loans l
JOIN books b ON l.book_id = b.book_id
JOIN categories c ON b.category_id = c.category_id
GROUP BY c.name
ORDER BY total_loans DESC;

-- 4. Aggregate report: total fines collected/unpaid
SELECT SUM(amount) AS total_fines,
       SUM(CASE WHEN paid THEN amount ELSE 0 END) AS paid_fines,
       SUM(CASE WHEN NOT paid THEN amount ELSE 0 END) AS unpaid_fines
FROM fines;

-- 5. Books with zero available copies
SELECT title, total_copies, available_copies
FROM books
WHERE available_copies = 0;