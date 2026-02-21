<?php

declare(strict_types=1);

namespace Shopware\Core\HeptaConnect\Command;

use Composer\Console\Input\InputOption;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class RemoveUnusedTables extends Command
{
    private const DELETABLES = [
        'acl_resource',
        'acl_user_role', // required by acl_role
        'integration_role', // required by acl_role
        'custom_field_set_relation', // required by custom_field_set
        'product_custom_field_set', // required by custom_field_set
        'product_search_config_field', // required by custom_field
        'custom_field', // required by custom_field_set
        'custom_field_set', // required by app, product_custom_field_set
        'app_action_button_translation', // required by app_action_button, language
        'app_action_button', // required by app
        'app_template', // required by app
        'app_cms_block_translation', // required by app_cms_block, language
        'app_cms_block', // required by app
        'script', // required by app
        'app_script_condition_translation', // required by app_script_condition, language
        'rule_condition', // required by rule, app_script_condition
        'app_script_condition', // required by app
        'app_flow_action_translation', // required by app_flow_action, language
        'flow_sequence', // required by app_flow_action
        'app_flow_action', // required by app
        'flow', // required by app_flow_event
        'app_flow_event', // required by app
        'tax_provider_translation', // required by tax_provider, language
        'tax_provider', // required by app
        'app_payment_method', // required by app
        'app_shipping_method', // required by app
        'webhook_event_log', // required by webhook
        'webhook', // required by app
        'app_translation', // required by app, language
        'category_translation', // required by category, language
        'category_tag', // required by category
        'product_category_tree', // required by category
        'product_category', // required by category
        'main_category', // required by category
        'navigation_translation', // required by navigation, language
        'sales_channel_type_translation', // required by sales_channel, language
        'sales_channel_language', // required by sales_channel
        'sales_channel_api_context', // required by sales_channel
        'sales_channel_country', // required by sales_channel
        'sales_channel_currency', // required by sales_channel
        'sales_channel_payment_method', // required by sales_channel
        'sales_channel_shipping_method', // required by sales_channel
        'sales_channel_translation', // required by sales_channel, language
        'product_export', // required by sales_channel_domain
        'sales_channel_domain', // required by sales_channel
        'sales_channel_analytics', // required by sales_channel
        'number_range_sales_channel', // required by sales_channel
        'document_base_config_sales_channel', // required by sales_channel
        'promotion_sales_channel', // required by sales_channel
        'product_visibility', // required by sales_channel
        'customer_group_registration_sales_channels', // required by sales_channel
        'seo_url_template', // required by sales_channel
        'event_action_sales_channel', // required by sales_channel
        'customer_wishlist_product', // required customer_wishlist
        'customer_wishlist', // required by sales_channel
        'landing_page_sales_channel', // required by sales_channel
        'product_review', // required by sales_channel
        'system_config', // required by sales_channel
        'seo_url', // required by sales_channel
        'newsletter_recipient_tag', // required by newsletter_recipient
        'newsletter_recipient', // required by sales_channel
        'customer_address', // required by customer
        'customer_recovery', // required by customer
        'customer_tag', // required by customer
        'promotion_persona_customer', // required by customer
        'order_customer', // required by customer
        'customer', // required by sales_channel
        'customer_group_translation', // required by customer_group, language
        'order_tag', // required by order
        'order_delivery_position', // required by order_delivery
        'order_delivery', // required by order
        'order_address', // required by order
        'order_transaction_capture_refund_position', // required by order
        'order_transaction_capture_refund', // required by order
        'order_transaction_capture', // required by order
        'order_line_item_download', // required by order_line_item
        'order_line_item', // required by order
        'order_transaction', // required by order
        'document', // required by order
        'order',
        'sales_channel', // required by sales_channel_type
        'sales_channel_type', // required by sales_channel
        'customer_group', // required by sales_channel
        'navigation', // required by category
        'category', // required by custom_entity
        'custom_entity', // required by app
        'app', // required by acl_role
        'acl_role',
        'app_config',
        'cart',
        'cms_slot_translation', // required by cms_slot, language
        'cms_slot', // required by cms_block
        'cms_block', // required by cms_section
        'cms_section', // required by cms_page
        'cms_page_translation', // required by cms_page, language
        'landing_page_translation', // required by landing_page, language
        'landing_page_tag', // required by landing_page
        'landing_page', // required by cms_page
        'product_media', // required by product
        'product_configurator_setting', // required by product
        'product_cross_selling_translation', // required by product_cross_selling, language
        'product_cross_selling_assigned_products', // required by product_cross_selling
        'product_cross_selling', // required by product
        'product_download', // required by product
        'product_option', // required by product
        'product_price', // required by product
        'product_property', // required by product
        'product_search_keyword', // required by product
        'product_tag', // required by product
        'product_translation', // required by product, language
        'product_stream_mapping', // required by product
        'product', // required by product_feature_set
        'cms_page', // required by landing_page, product
        'product_feature_set_translation', // required by product_feature_set, language
        'product_feature_set',
        'product_manufacturer_translation', // required by product_manufacturer, language
        'product_manufacturer', // required by product
        'product_keyword_dictionary',
        'product_search_config',
        'product_sorting_translation', // required by product_sorting, language
        'product_sorting',
        'product_stream_translation', // required by product_stream, language
        'product_stream_filter', // required by product_stream
        'product_stream',
        'country_state_translation', // required by country_state, language
        'country_state', // required by country
        'country_translation', // required by country, language
        'currency_country_rounding', // required by country
        'tax_rule', // required by country
        'country',
        'currency_translation', // required by currency, language
        'promotion_discount_prices', // required by currency
        'shipping_method_price', // required by currency
        'currency',
        'dead_message',
        'shipping_method_translation', // required by shipping_method, language
        'shipping_method_tag', // required by shipping_method
        'shipping_method', // required by delivery_time
        'delivery_time_translation', // required by delivery_time, language
        'delivery_time',
        'document_base_config',
        'document_type_translation', // required by document_type, language
        'document_type',
        'enqueue',
        'event_action_rule', // required by event_action
        'event_action',
        'flow_template',
        'import_export_log', // required by import_export_file
        'import_export_file',
        'import_export_profile_translation', // required by import_export_profile, language
        'import_export_profile',
        'increment',
        'integration',
        'locale_translation', // required by locale, language
        'promotion_translation', // required by language
        'property_group_option_translation', // required by language
        'property_group_translation', // required by language
        'salutation_translation', // required by language
        'state_machine_state_translation', // required by language
        'state_machine_translation', // required by language
        'tax_rule_type_translation', // required by language
        'unit_translation', // required by language
        'mail_header_footer_translation', // required by language
        'mail_template_translation', // required by language
        'mail_template_type_translation', // required by language
        'media_translation', // required by language
        'number_range_translation', // required by language
        'number_range_type_translation', // required by language
        'payment_method_translation', // required by language
        'plugin_translation', // required by language
        'mail_template_media', // required by language
        'language',
        'user_recovery', // required by user
        'user_access_key', // required by user
        'user_config', // required by user
        'state_machine_history', // required by user
        'state_machine_transition', // required by state_machine
        'state_machine_state', // required by state_machine
        'state_machine',
        'media_thumbnail', // required by media
        'media_tag', // required by media
        'media_folder_configuration_media_thumbnail_size', // required by media_thumbnail_size
        'media_thumbnail_size',
        'property_group_option', // required by property_group
        'property_group',
        'payment_method', // required by media
        'media',
        'user',
        'locale',
        'log_entry',
        'mail_header_footer',
        'mail_template',
        'mail_template_type',
        'media_folder_configuration', // required by media_folder
        'media_folder', // required by media_default_folder
        'media_default_folder',
        'messenger_messages',
        'migration',
        'number_range_state', // required by number_range
        'number_range_type', // required by number_range
        'number_range',
        'payment_token',
        'plugin',
        'promotion_cart_rule', // required by promotion
        'promotion_discount_rule', // required by promotion_discount
        'promotion_discount', // required by promotion
        'promotion_individual_code', // required by promotion
        'promotion_order_rule', // required by promotion
        'promotion_persona_rule', // required by promotion
        'promotion_setgroup_rule', // required by promotion_setgroup
        'promotion_setgroup', // required by promotion
        'promotion',
        'refresh_token',
        'rule_tag', // required by rule
        'rule',
        'salutation',
        'scheduled_task',
        'snippet',
        'snippet_set', // required by snippet
        'tag',
        'tax',
        'tax_rule_type',
        'unit',
        'usage_data_entity_deletion',
        'version',
        'version_commit_data', // required by version_commit
        'version_commit',
    ];

    private Connection $connection;

    public function __construct(
        Connection $connection,
    ) {
        $this->connection = $connection;
        parent::__construct('hepta-connect:shopware:remove-unused-tables');
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Delete likely unused database tables. Choose wisely and have a backup. The preset is based upon a clean installation. Can vary per project.');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip interactive check');
        $this->addOption('skip-tables', 't', InputOption::VALUE_IS_ARRAY, 'Tables to skip');
    }

    #[\Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $schemaManager = $this->connection->createSchemaManager();
        $io = new SymfonyStyle($input, $output);
        $skip = \array_filter((array) $input->getOption('skip-tables'));
        $availableTables = $schemaManager->listTableNames();
        $availableTables = \array_values(\array_intersect($availableTables, self::DELETABLES));

        if (!$input->isInteractive() && !$input->getOption('force')) {
            $io->error('Skipping removal as accidental non-interactive usage is not allowed. Ensure to have interactive terminal for guided deletion or pass --force to allow automatic removal. Read about --skip-tables option.');
            return Command::INVALID;
        }

        $tablesToDelete = $io->choice('Which tables to delete?', $availableTables, \array_values(\array_diff($availableTables, $skip)), true);

        foreach ($tablesToDelete as $table) {
            if ($table === 'state_machine_state') {
                $this->connection->executeStatement('ALTER TABLE `state_machine` DROP FOREIGN KEY `fk.state_machine.initial_state_id`');
            }

            if ($table === 'sales_channel_domain') {
                $this->connection->executeStatement('ALTER TABLE `sales_channel` DROP FOREIGN KEY `fk.sales_channel.hreflang_default_domain_id`');
            }

            if ($table === 'sales_channel_analytics') {
                $this->connection->executeStatement('ALTER TABLE `sales_channel` DROP FOREIGN KEY `fk.sales_channel.analytics_id`');
            }

            if ($table === 'media') {
                $this->connection->executeStatement('ALTER TABLE `user` DROP FOREIGN KEY `fk.user.avatar_id`');
            }

            $schemaManager->dropTable($table);
        }

        return Command::SUCCESS;
    }
}
