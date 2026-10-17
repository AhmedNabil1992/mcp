<?php

declare(strict_types=1);

namespace Webkul\Mcp\Support;

final class BusinessDataCatalog
{
    /**
     * @return array<string, array{table: string, description: string}>
     */
    public static function entities(): array
    {
        return [
            'accounts'             => ['table' => 'accounts_accounts', 'description' => 'Chart of accounts'],
            'articles'             => ['table' => 'articles_articles', 'description' => 'Knowledge-base articles'],
            'bank_statements'      => ['table' => 'accounts_bank_statements', 'description' => 'Accounting bank statements'],
            'blog_posts'           => ['table' => 'blogs_posts', 'description' => 'Website blog posts'],
            'campaigns'            => ['table' => 'marketing_campaigns', 'description' => 'Marketing campaigns'],
            'companies'            => ['table' => 'companies', 'description' => 'ERP companies'],
            'departments'          => ['table' => 'employees_departments', 'description' => 'Employee departments'],
            'employees'            => ['table' => 'employees_employees', 'description' => 'Employees'],
            'expenses'             => ['table' => 'expenses_expenses', 'description' => 'Employee expenses'],
            'inventory_locations'  => ['table' => 'inventories_locations', 'description' => 'Inventory locations'],
            'inventory_operations' => ['table' => 'inventories_operations', 'description' => 'Warehouse operations'],
            'inventory_quantities' => ['table' => 'inventories_product_quantities', 'description' => 'On-hand product quantities'],
            'invoices'             => ['table' => 'accounts_account_moves', 'description' => 'Customer invoices, vendor bills and credit notes'],
            'invoice_lines'        => ['table' => 'accounts_account_move_lines', 'description' => 'Invoice and journal lines'],
            'leads'                => ['table' => 'leads_leads', 'description' => 'CRM leads and opportunities'],
            'online_instances'     => ['table' => 'online_instances', 'description' => 'Hosted software instances'],
            'partners'             => ['table' => 'partners_partners', 'description' => 'Customers, vendors and contacts'],
            'payments'             => ['table' => 'accounts_account_payments', 'description' => 'Accounting payments'],
            'payment_transactions' => ['table' => 'payments_payment_transactions', 'description' => 'Online payment transactions'],
            'products'             => ['table' => 'products_products', 'description' => 'Products and services'],
            'projects'             => ['table' => 'projects_projects', 'description' => 'Projects'],
            'project_tasks'        => ['table' => 'projects_tasks', 'description' => 'Project tasks'],
            'purchase_orders'      => ['table' => 'purchases_orders', 'description' => 'Purchase orders'],
            'purchase_requisitions'=> ['table' => 'purchases_requisitions', 'description' => 'Purchase requisitions'],
            'referral_campaigns'   => ['table' => 'referral_campaigns', 'description' => 'Referral campaigns'],
            'referral_redemptions' => ['table' => 'referral_redemptions', 'description' => 'Referral reward redemptions'],
            'sales_orders'         => ['table' => 'sales_orders', 'description' => 'Sales quotations and orders'],
            'software_licenses'    => ['table' => 'software_licenses', 'description' => 'Desktop software licenses'],
            'support_tickets'      => ['table' => 'technical_support_tickets', 'description' => 'Technical support tickets'],
            'time_off_allocations' => ['table' => 'time_off_leave_allocations', 'description' => 'Employee leave allocations'],
            'time_off_requests'    => ['table' => 'time_off_leaves', 'description' => 'Employee leave requests'],
            'vpn_servers'          => ['table' => 'vpn_servers', 'description' => 'VPN servers'],
            'warehouses'           => ['table' => 'inventories_warehouses', 'description' => 'Inventory warehouses'],
            'website_pages'        => ['table' => 'website_pages', 'description' => 'Website pages'],
            'wifi_packages'        => ['table' => 'wifi_packages', 'description' => 'WiFi service packages'],
            'wifi_purchases'       => ['table' => 'wifi_purchases', 'description' => 'WiFi package purchases'],
            'wifi_voucher_batches' => ['table' => 'wifi_voucher_batches', 'description' => 'WiFi voucher batches'],
        ];
    }

    /** @return array<int, string> */
    public static function sensitiveColumns(): array
    {
        return [
            'password', 'password_hash', 'remember_token', 'token', 'access_token', 'refresh_token',
            'api_key', 'api_secret', 'secret', 'private_key', 'pin', 'serial_number', 'license_key',
        ];
    }
}
