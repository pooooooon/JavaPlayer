<?php

declare(strict_types=1);

namespace pooooooon\javaplayer\network;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\entity\Entity;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPaintingPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo as Info;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\RequestAbilityPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\SetDifficultyPacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetHealthPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTimePacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pooooooon\javaplayer\entity\ItemFrameBlockEntity;
use pooooooon\javaplayer\Loader;
use pooooooon\javaplayer\network\javadata\JavaEntityId;
use pooooooon\javaplayer\network\javadata\JavaTileName;
use pooooooon\javaplayer\network\protocol\Login\LoginDisconnectPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\AdvancementTabPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\ClientStatusPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\CreativeInventoryActionPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\EntityActionPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\InteractEntityPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\KeepAlivePacket as ClientKeepAlivePacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerAbilitiesPacket as ClientPlayerAbilitiesPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerBlockPlacementPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerDiggingPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerMovementPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerPositionPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerRotationPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\UpdateSignPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\BlockActionPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\BlockChangePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\BlockEntityDataPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\BossBarPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\ChangeGameStatePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\ChatMessagePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\DestroyEntitiesPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\ClearTitlesPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\DisplayScoreboardPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EffectPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityAnimationPacket as STCAnimatePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityEffectPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityEquipmentPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityHeadLookPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityMetadataPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityRotationPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityStatusPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityTeleportPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityVelocityPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\HeldItemChangePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\JoinGamePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\KeepAlivePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\MapPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\NamedSoundEffectPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\ParticlePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\PlayDisconnectPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\PlayerAbilitiesPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\PlayerInfoPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\PlayerPositionAndLookPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\PluginMessagePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\RemoveEntityEffectPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\RespawnPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\ScoreboardObjectivePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SelectAdvancementTabPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\ServerDifficultyPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SpawnEntityPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SetActionBarTextPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SetSubtitleTextPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SetTitlesAnimationPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SetTitleTextPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SpawnExperienceOrbPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SpawnLivingEntityPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SpawnPaintingPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SpawnPlayerPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SpawnPositionPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\StatisticsPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\TimeUpdatePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\TitlePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UpdateHealthPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UpdateScorePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UpdateViewDistancePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UpdateViewPositionPacket;
use pooooooon\javaplayer\task\chunktask;
use pooooooon\javaplayer\utils\ConvertUtils;
use pooooooon\javaplayer\utils\JavaBinarystream;
use Ramsey\Uuid\Nonstandard\Uuid;
use ReflectionClass;
use UnexpectedValueException;

class Translator
{

	/**
	 * @param JavaPlayerNetworkSession $player
	 * @param Packet $packet
	 * @return DataPacket|array<DataPacket>|null
	 */
	public function interfaceToServer(JavaPlayerNetworkSession $player, Packet $packet)
	{
		switch ($packet->pid()) {
			case InboundPacket::TELEPORT_CONFIRM_PACKET://Teleport Confirm
			case InboundPacket::WINDOW_CONFIRMATION_PACKET://Transaction Confirm
			case InboundPacket::TAB_COMPLETE_PACKET:
				return null;

			case InboundPacket::CHAT_MESSAGE_PACKET:
				/** @var protocol\Play\Client\ChatMessagePacket $packet */
				if (substr($packet->message, 0, 12) === ")respondform") {
					if (!isset($player->bigBrother_formId)) {
						$player->getPlayer()->sendMessage(TextFormat::RED . "Form already closed.");
						return null;
					}
					$value = explode(" ", $packet->message)[1];

					$response = new ModalFormResponsePacket();
					$response->formId = $player->bigBrother_formId;
					if ($value === "ESC") {
						$value = null;
					} else {
						$value = intval($value);
					}
					$response->formData = json_encode($value);

					unset($player->bigBrother_formId);
					return $response;
				}
				$player->getPlayer()->chat($packet->message);
				return null;

			case InboundPacket::CLIENT_STATUS_PACKET:
				/** @var ClientStatusPacket $packet */
				switch ($packet->actionId) {
					case 0:
						$player->getPlayer()->respawn();
					case 1:
						//TODO: stat https://gist.github.com/Alvin-LB/8d0d13db00b3c00fd0e822a562025eff
						$statistic = [];

						$pk = new StatisticsPacket();
						$pk->count = count($statistic);
						$pk->statistic = $statistic;
						$player->putRawPacket($pk);
						break;
					default:
						echo "ClientStatusPacket: " . $packet->actionId . "\n";
						break;
				}
				return null;

			// case InboundPacket::CLIENT_SETTINGS_PACKET:
			// 	/** @var ClientSettingsPacket $packet */
			// 	$player->bigBrother_setClientSetting([
			// 		"ChatMode" => $packet->chatMode,
			// 		"ChatColor" => $packet->chatColors,
			// 		"SkinSettings" => $packet->displayedSkinParts,
			// 	]);

			// 	$locale = $packet->lang[0].$packet->lang[1];
			// 	if(isset($packet->lang[2])){
			// 		$locale .= $packet->lang[2].strtoupper($packet->lang[3].$packet->lang[4]);
			// 	}
			// 	$player->setLocale($locale);

			// 	// $pk = new EntityMetadataPacket();
			// 	// $pk->entityId = $player->getPlayer()->getId();
			// 	// $pk->metadata = [//Enable Display Skin Parts
			// 	// 	16 => [0, $packet->displayedSkinParts],
			// 	// 	"convert" => true,
			// 	// ];
			// 	// $loggedInPlayers = Server::getInstance()->getOnlinePlayers();
			// 	// foreach($loggedInPlayers as $playerData){
			// 	// 	if($playerData->getNetworkSession() instanceof JavaPlayerNetworkSession){
			// 	// 		$playerData->getNetworkSession()->putRawPacket($pk);
			// 	// 	}
			// 	// }

			// 	$pk = new RequestChunkRadiusPacket();
			// 	$pk->radius = $packet->viewDistance;

			// 	return $pk;

			// case InboundPacket::CLICK_WINDOW_PACKET:
			// 	/** @var ClickWindowPacket $packet */
			// 	$pk = $player->getInventoryUtils()->onWindowClick($packet);

			// 	return $pk;

			// case InboundPacket::CLOSE_WINDOW_PACKET:
			// 	/** @var CloseWindowPacket $packet */
			// 	$pk = $player->getInventoryUtils()->onWindowCloseFromPCtoPE($packet);

			// 	return $pk;

			case InboundPacket::PLUGIN_MESSAGE_PACKET:
				/** @var PluginMessagePacket $packet */
				switch ($packet->channel) {
					case "minecraft:brand":
						//TODO: brand
						break;
					/*case "MC|BEdit":
						$packets = [];
						/** @var Item $item *//*
						$item = clone $packet->data[0];

						if(!is_null(($pages = $item->getNamedTagEntry("pages")))){
							foreach($pages as $pageNumber => $pageTags){
								if($pageTags instanceof CompoundTag){
									foreach($pageTags as $name => $tag){
										if($tag instanceof StringTag){
											if($tag->getName() === "text"){
												$pk = new BookEditPacket();
												$pk->type = BookEditPacket::TYPE_REPLACE_PAGE;
												$pk->inventorySlot = $player->getPlayer()->getInventory()->getHeldItemIndex() + 9;
												$pk->pageNumber = (int) $pageNumber;
												$pk->text = $tag->getValue();
												$pk->photoName = "";//Not implement

												$packets[] = $pk;
											}
										}
									}
								}
							}
						}

						return $packets;
					case "MC|BSign":
						$packets = [];
						/** @var Item $item *//*
						$item = clone $packet->data[0];

						if(!is_null(($pages = $item->getNamedTagEntry("pages")))){
							foreach($pages as $pageNumber => $pageTags){
								if($pageTags instanceof CompoundTag){
									foreach($pageTags as $name => $tag){
										if($tag instanceof StringTag){
											if($tag->getName() === "text"){
												$pk = new BookEditPacket();
												$pk->type = BookEditPacket::TYPE_REPLACE_PAGE;
												$pk->inventorySlot = $player->getPlayer()->getInventory()->getHeldItemIndex() + 9;
												$pk->pageNumber = (int) $pageNumber;
												$pk->text = $tag->getValue();
												$pk->photoName = "";//Not implement

												$packets[] = $pk;
											}
										}
									}
								}
							}
						}

						$pk = new BookEditPacket();
						$pk->type = BookEditPacket::TYPE_SIGN_BOOK;
						$pk->inventorySlot = $player->getPlayer()->getInventory()->getHeldItemIndex();
						$pk->title = $item->getNamedTagEntry("title")->getValue();
						$pk->author = $item->getNamedTagEntry("author")->getValue();

						$packets[] = $pk;

						return $packets;
					break;*/
				}
				return null;

			case InboundPacket::INTERACT_ENTITY_PACKET:
				/** @var InteractEntityPacket $packet */
				$frame = ItemFrameBlockEntity::getItemFrameById($player->getPlayer()->getWorld(), $packet->target);
				if ($frame !== null) {
					switch ($packet->type) {
						case InteractEntityPacket::TYPE_INTERACT:
							$clickPos = new Vector3($frame->x, $frame->y, $frame->z);
							$pk = new InventoryTransactionPacket();
							$pk->trData = UseItemTransactionData::new(
								[],
								UseItemTransactionData::ACTION_CLICK_BLOCK,
								BlockPosition::fromVector3($clickPos),
								$frame->getFacing(),
								$player->getPlayer()->getInventory()->getHeldItemIndex(),
								ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($player->getPlayer()->getInventory()->getItemInHand())),
								$player->getPlayer()->getPosition()->asVector3(),
								$frame->asVector3(),
								RuntimeBlockMapping::getInstance()->toRuntimeId($player->getPlayer()->getWorld()->getBlock($clickPos)->getFullId()));
							return $pk;
						case InteractEntityPacket::TYPE_ATTACK:
							if ($frame->hasItem()) {
								$pk = new ItemFrameDropItemPacket();
								$pk->blockPosition = BlockPosition::fromVector3($frame);

								return $pk;
							} else {
								$clickPos = new Vector3($frame->x, $frame->y, $frame->z);
								$pk = new InventoryTransactionPacket();
								$pk->trData = UseItemTransactionData::new(
									[],
									UseItemTransactionData::ACTION_BREAK_BLOCK,
									BlockPosition::fromVector3($clickPos),
									$frame->getFacing(),
									$player->getPlayer()->getInventory()->getHeldItemIndex(),
									ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($player->getPlayer()->getInventory()->getItemInHand())),
									$player->getPlayer()->getPosition()->asVector3(),
									$frame->asVector3(),
									RuntimeBlockMapping::getInstance()->toRuntimeId($player->getPlayer()->getWorld()->getBlock($clickPos)->getFullId()));
								return $pk;
							}
					}

					return null;
				}

				if ($packet->type === InteractEntityPacket::TYPE_INTERACT_AT) {
					$pk = new InteractPacket();
					$pk->targetActorRuntimeId = $packet->target;
					$pk->action = InteractPacket::ACTION_MOUSEOVER;
					$pk->x = 0;
					$pk->y = 0;
					$pk->z = 0;
				} else {
					switch ($packet->type) {
						case InteractEntityPacket::TYPE_INTERACT:
							$actionType = UseItemOnEntityTransactionData::ACTION_INTERACT;
							break;
						case InteractEntityPacket::TYPE_ATTACK:
							$actionType = UseItemOnEntityTransactionData::ACTION_ATTACK;
							break;
						case InteractEntityPacket::TYPE_INTERACT_AT:
							$actionType = UseItemOnEntityTransactionData::ACTION_ITEM_INTERACT;
							break;
					}
					$pk = new InventoryTransactionPacket();
					$pk->trData = UseItemOnEntityTransactionData::new(
						[],
						$packet->target,
						$actionType,
						$player->getPlayer()->getInventory()->getHeldItemIndex(),
						ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($player->getPlayer()->getInventory()->getItemInHand())),
						$player->getPlayer()->getPosition()->asVector3(),
						new Vector3(0, 0, 0)
					);
				}

				return $pk;

			case InboundPacket::KEEP_ALIVE_PACKET:
				assert($packet instanceof ClientKeepAlivePacket);
				$pk = new KeepAlivePacket();
				$pk->keepAliveId = date_create()->format('Uv');
				$player->putRawPacket($pk);
				$player->updatePing($pk->keepAliveId - $packet->keepAliveId);

				return null;

			case InboundPacket::PLAYER_MOVEMENT_PACKET:
				/** @var PlayerMovementPacket $packet */
				$player->getPlayer()->onGround = $packet->onGround;

				return null;

			case InboundPacket::PLAYER_POSITION_PACKET:
				// var_dump($packet);
				/** @var PlayerPositionPacket $packet */
				if ($player->getPlayer()->isImmobile()) {
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->getPlayer()->getPosition()->x;
					$pk->y = $player->getPlayer()->getPosition()->y;
					$pk->z = $player->getPlayer()->getPosition()->z;
					$pk->yaw = $player->getPlayer()->getLocation()->yaw;
					$pk->pitch = $player->getPlayer()->getLocation()->pitch;
					$pk->onGround = $player->getPlayer()->isOnGround();
					$player->putRawPacket($pk);
					return null;
				}

				$position = new Vector3($packet->x, $packet->feetY + $player->getPlayer()->getEyeHeight(), $packet->z);
				$newPos = $position->round(4)->subtract(0, 1.62, 0);
				// $curPos = $player->getPlayer()->getLocation();
				// if ($this->forceMoveSync && $newPos->distanceSquared($curPos) > 1) {  //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
				// 	Server::getInstance()->getLogger()->debug("Got outdated pre-teleport movement, received " . $newPos . ", expected " . $curPos);
				// 	//Still getting movements from before teleport, ignore them
				// 	return null;
				// }

				if ($player->getPlayer()->isOnGround() and !$packet->onGround) {
					$player->getPlayer()->jump();
				}
				// var_dump($newPos);
				$player->getPlayer()->handleMovement($newPos);
				

				return null;

			case InboundPacket::PLAYER_POSITION_AND_ROTATION_PACKET:
				
				/** @var protocol\Play\Client\PlayerPositionAndRotationPacket $packet */
				if ($player->getPlayer()->isImmobile()) {
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->getPlayer()->getPosition()->x;
					$pk->y = $player->getPlayer()->getPosition()->y;
					$pk->z = $player->getPlayer()->getPosition()->z;
					$pk->yaw = $player->getPlayer()->getLocation()->yaw;
					$pk->pitch = $player->getPlayer()->getLocation()->pitch;
					$pk->onGround = $player->getPlayer()->isOnGround();
					$player->putRawPacket($pk);

					return null;
				}
				// var_dump($packet);
				$packets = [];

				$position = new Vector3($packet->x, $packet->feetY + $player->getPlayer()->getEyeHeight(), $packet->z);
				$newPos = $position->round(4)->subtract(0, 1.62, 0);
				// var_dump($newPos);
				// $curPos = $player->getPlayer()->getLocation();
				// if ($this->forceMoveSync && $newPos->distanceSquared($curPos) > 1) {  //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
				// 	Server::getInstance()->getLogger()->debug("Got outdated pre-teleport movement, received " . $newPos . ", expected " . $curPos);
				// 	//Still getting movements from before teleport, ignore them
				// 	return null;
				// }
				$yaw = fmod($packet->yaw, 360);
				$pitch = fmod($packet->pitch, 360);
				if ($yaw < 0) {
					$yaw += 360;
				}

				$player->getPlayer()->setRotation($yaw, $pitch);

				if ($player->getPlayer()->isOnGround() and !$packet->onGround) {
					$player->getPlayer()->jump();
				}
				// var_dump($newPos);
				$player->getPlayer()->handleMovement($newPos);
				

				return $packets;

			case InboundPacket::PLAYER_ROTATION_PACKET:
				/** @var PlayerRotationPacket $packet */
				if ($player->getPlayer()->isImmobile()) {
					$pk = new PlayerPositionAndLookPacket();
					$pk->x = $player->getPlayer()->getPosition()->x;
					$pk->y = $player->getPlayer()->getPosition()->y;
					$pk->z = $player->getPlayer()->getPosition()->z;
					$pk->yaw = $player->getPlayer()->getLocation()->yaw;
					$pk->pitch = $player->getPlayer()->getLocation()->pitch;
					$pk->onGround = $player->getPlayer()->isOnGround();
					$player->putRawPacket($pk);

					return null;
				}
				$yaw = fmod($packet->yaw, 360);
				$pitch = fmod($packet->pitch, 360);
				if ($yaw < 0) {
					$yaw += 360;
				}

				$player->getPlayer()->setRotation($yaw, $pitch);

				return null;

			case InboundPacket::PLAYER_ABILITIES_PACKET:
				/** @var ClientPlayerAbilitiesPacket $packet */
				$pk = RequestAbilityPacket::create(RequestAbilityPacket::ABILITY_FLYING, $packet->isFlying);
				return $pk;

			case InboundPacket::PLAYER_DIGGING_PACKET:
				/** @var PlayerDiggingPacket $packet */
				switch ($packet->status) {
					case 0:
						if ($player->getPlayer()->getGamemode() === GameMode::CREATIVE()) {
							$clickPos = new Vector3($packet->x, $packet->y, $packet->z);
							$pk = new InventoryTransactionPacket();

							$pk->trData = UseItemTransactionData::new(
								[],
								UseItemTransactionData::ACTION_BREAK_BLOCK,
								BlockPosition::fromVector3($clickPos),
								$packet->face,
								$player->getPlayer()->getInventory()->getHeldItemIndex(),
								ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($player->getPlayer()->getInventory()->getItemInHand())),
								$player->getPlayer()->getPosition()->asVector3(),
								$clickPos,
								RuntimeBlockMapping::getInstance()->toRuntimeId($player->getPlayer()->getWorld()->getBlock($clickPos)->getFullId())
							);
							return $pk;
						} else {
							$player->bigBrother_breakPosition = [new Vector3($packet->x, $packet->y, $packet->z), $packet->face];

							$packets = [];

							$pk = new PlayerActionPacket();
							$pk->actorRuntimeId = $player->getPlayer()->getId();
							$pk->action = PlayerAction::START_BREAK;
							$pk->x = $packet->x;
							$pk->y = $packet->y;
							$pk->z = $packet->z;
							$pk->face = $packet->face;
							$packets[] = $pk;

							$block = $player->getPlayer()->getWorld()->getBlock(new Vector3($packet->x, $packet->y, $packet->z));
							if ($block->getBreakInfo()->getHardness() === (float)0) {
								$pk = new PlayerActionPacket();
								$pk->actorRuntimeId = $player->getPlayer()->getId();
								$pk->action = PlayerAction::STOP_BREAK;
								$pk->x = $packet->x;
								$pk->y = $packet->y;
								$pk->z = $packet->z;
								$pk->face = $packet->face;
								$packets[] = $pk;

								$clickPos = new Vector3($packet->x, $packet->y, $packet->z);
								$pk = new InventoryTransactionPacket();

								$pk->trData = UseItemTransactionData::new(
									[],
									UseItemTransactionData::ACTION_BREAK_BLOCK,
									BlockPosition::fromVector3($clickPos),
									$packet->face,
									$player->getPlayer()->getInventory()->getHeldItemIndex(),
									ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($player->getPlayer()->getInventory()->getItemInHand())),
									$player->getPlayer()->getPosition()->asVector3(),
									$clickPos,
									RuntimeBlockMapping::getInstance()->toRuntimeId($block->getFullId())
								);

								$packets[] = $pk;

								$pk = new PlayerActionPacket();
								$pk->actorRuntimeId = $player->getPlayer()->getId();
								$pk->action = PlayerAction::ABORT_BREAK;
								$pk->x = $packet->x;
								$pk->y = $packet->y;
								$pk->z = $packet->z;
								$pk->face = $packet->face;
								$packets[] = $pk;
							}

							return $packets;
						}
					case 1:
						$player->bigBrother_breakPosition = [new Vector3(0, 0, 0), 0];

						$pk = new PlayerActionPacket();
						$pk->actorRuntimeId = $player->getPlayer()->getId();
						$pk->action = PlayerAction::ABORT_BREAK;
						$pk->blockPosition = new BlockPosition($packet->x, $packet->y, $packet->z);
						$pk->face = $packet->face;

						return $pk;
					case 2:
						if ($player->getPlayer()->getGamemode() !== GameMode::CREATIVE()) {
							$player->bigBrother_breakPosition = [new Vector3(0, 0, 0), 0];

							$packets = [];

							$pk = new PlayerActionPacket();
							$pk->actorRuntimeId = $player->getPlayer()->getId();
							$pk->action = PlayerAction::STOP_BREAK;
							$pk->blockPosition = new BlockPosition($packet->x, $packet->y, $packet->z);
							$pk->face = $packet->face;
							$packets[] = $pk;

							$clickPos = new Vector3($packet->x, $packet->y, $packet->z);
							$pk = new InventoryTransactionPacket();
							$pk->trData = UseItemTransactionData::new(
								[],
								UseItemTransactionData::ACTION_BREAK_BLOCK,
								BlockPosition::fromVector3($clickPos),
								$packet->face,
								$player->getPlayer()->getInventory()->getHeldItemIndex(),
								ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($player->getPlayer()->getInventory()->getItemInHand())),
								$player->getPlayer()->getPosition()->asVector3(),
								$clickPos,
								RuntimeBlockMapping::getInstance()->toRuntimeId($player->getPlayer()->getWorld()->getBlock($clickPos)->getFullId())
							);
							$packets[] = $pk;

							$pk = new PlayerActionPacket();
							$pk->actorRuntimeId = $player->getPlayer()->getId();
							$pk->action = PlayerAction::ABORT_BREAK;
							$pk->blockPosition = new BlockPosition($packet->x, $packet->y, $packet->z);
							$pk->face = $packet->face;
							$packets[] = $pk;

							return $packets;
						} else {
							echo "PlayerDiggingPacket: " . $packet->status . "\n";
						}
						break;
					case 3:
					case 4:
						$newItem = clone $player->getPlayer()->getInventory()->getItemInHand();
						$oldItem = clone $newItem;

						if (!$newItem->isNull()) {
							if ($packet->status === 4) {
								$pop = $newItem->pop();
								$dropItem = $pop;
							} else {
								$dropItem = clone $newItem;
								$newItem = VanillaItems::AIR();
							}
							$actions = [];
							$action = new NetworkInventoryAction();
							$action->sourceType = 2;
							$action->sourceFlags = 0;
							$action->inventorySlot = 0;
							$action->oldItem = ItemStackWrapper::legacy(ItemStack::null());
							$action->newItem = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($dropItem));
							$actions[] = $action;

							$action = new NetworkInventoryAction();
							$action->sourceType = 0;
							$action->windowId = ContainerIds::INVENTORY;
							$action->inventorySlot = $player->getPlayer()->getInventory()->getHeldItemIndex();
							$action->oldItem = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($oldItem));
							$action->newItem = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($newItem));
							$actions[] = $action;

							/** @var InventoryTransactionPacket[] $packets */
							$packets = [];
							$pk = new InventoryTransactionPacket();
							$pk->trData = NormalTransactionData::new(
								$actions
							);
							$packets[] = $pk;

							$pk = new InventoryTransactionPacket();

							$pk->trData = MismatchTransactionData::new();
							$packets[] = $pk;

							return $packets;
						}

						return null;
					case 5:
						$headPos = new Vector3($packet->x, $packet->y, $packet->z);
						$item = $player->getPlayer()->getInventory()->getItemInHand();
						if ($item->getId() === ItemIds::BOW) {//Shoot Arrow
							$actionType = ReleaseItemTransactionData::ACTION_RELEASE;
						} else {//Eating
							$actionType = ReleaseItemTransactionData::ACTION_CONSUME;
						}

						$pk = new InventoryTransactionPacket();
						$pk->trData = ReleaseItemTransactionData::new(
							[],
							$actionType,
							$player->getPlayer()->getInventory()->getHeldItemIndex(),
							ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($item)),
							$headPos
						);

						return $pk;
					default:
						echo "PlayerDiggingPacket: " . $packet->status . "\n";
						break;
				}

				return null;

			case InboundPacket::ENTITY_ACTION_PACKET:
				/** @var EntityActionPacket $packet */
				$pk = new PlayerActionPacket();
				$pk->actorRuntimeId = $player->getPlayer()->getId();
				$pk->blockPosition = new BlockPosition(0, 0, 0);
				$pk->face = 0;

				switch ($packet->actionId) {
					case 0://Start sneaking
						$pk->action = PlayerAction::START_SNEAK;

						return $pk;
					case 1://Stop sneaking
						$pk->action = PlayerAction::STOP_SNEAK;

						return $pk;
					case 2://leave bed
						$pk->action = PlayerAction::STOP_SLEEPING;

						return $pk;
					case 3://Start sprinting
						$pk->action = PlayerAction::START_SPRINT;

						return $pk;
					case 4://Stop sprinting
						$pk->action = PlayerAction::STOP_SPRINT;

						return $pk;
					default:
						echo "EntityActionPacket: " . $packet->actionId . "\n";
						break;
				}

				return null;

			case InboundPacket::ADVANCEMENT_TAB_PACKET:
				/** @var AdvancementTabPacket $packet */
				if ($packet->status === 0) {
					$pk = new SelectAdvancementTabPacket();
					$pk->hasId = true;
					$pk->identifier = $packet->tabId;
					$player->putRawPacket($pk);
				}

				return null;

			case InboundPacket::HELD_ITEM_CHANGE_PACKET:
				/** @var HeldItemChangePacket $packet */
				$pk = new MobEquipmentPacket();
				$pk->actorRuntimeId = $player->getPlayer()->getId();
				$pk->item = ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($player->getPlayer()->getInventory()->getHotbarSlotItem($packet->selectedSlot)));
				$pk->inventorySlot = $packet->selectedSlot;
				$pk->hotbarSlot = $packet->selectedSlot;

				return $pk;

			case InboundPacket::CREATIVE_INVENTORY_ACTION_PACKET:
				/** @var CreativeInventoryActionPacket $packet */
				var_dump($packet);
				$pk = $player->getInventoryUtils()->onCreativeInventoryAction($packet);

				return $pk;

			case InboundPacket::UPDATE_SIGN_PACKET:
				/** @var UpdateSignPacket $packet */
				$tags = CompoundTag::create()
					->setString(Tile::TAG_ID, JavaTileName::SIGN)
					->setString("Text1", $packet->line1)
					->setString("Text2", $packet->line2)
					->setString("Text3", $packet->line3)
					->setString("Text4", $packet->line4)
					->setInt("x", (int)$packet->x)
					->setInt("y", (int)$packet->y)
					->setInt("z", (int)$packet->z);
				$pk = new BlockActorDataPacket();
				$pk->blockPosition = new BlockPosition($packet->x, $packet->y, $packet->z);
				$pk->nbt = new CacheableNbt($tags);

				return $pk;

			case InboundPacket::ANIMATION_PACKET:
				$pk = new AnimatePacket();
				$pk->action = 1;
				$pk->actorRuntimeId = $player->getPlayer()->getId();

				$pos = $player->bigBrother_breakPosition;
				/**
				 * @var Vector3[] $pos
				 * @phpstan-var array{Vector3, int} $pos
				 */
				if (!$pos[0]->equals(new Vector3(0, 0, 0))) {
					$packets = [$pk];

					$pk = new PlayerActionPacket();
					$pk->actorRuntimeId = $player->getPlayer()->getId();
					$pk->action = PlayerAction::CONTINUE_DESTROY_BLOCK;
					$pk->blockPosition = new BlockPosition($pos[0]->x, $pos[0]->y, $pos[0]->z);
					$pk->face = $player->getPlayer()->getHorizontalFacing(); //it was $pos[1]
					$packets[] = $pk;

					return $packets;
				}

				return $pk;

			case InboundPacket::PLAYER_BLOCK_PLACEMENT_PACKET:
				/** @var PlayerBlockPlacementPacket $packet */
				$blockClicked = $player->getPlayer()->getWorld()->getBlock(new Vector3($packet->x, $packet->y, $packet->z));
				$blockReplace = $blockClicked->getSide($packet->face);

				if (ItemFrameBlockEntity::exists($player->getPlayer()->getWorld(), $blockReplace->getPosition()->getX(), $blockReplace->getPosition()->getY(), $blockReplace->getPosition()->getZ())) {
					$pk = new BlockChangePacket();//Cancel place block
					$pk->x = $blockReplace->getPosition()->getX();
					$pk->y = $blockReplace->getPosition()->getY();
					$pk->z = $blockReplace->getPosition()->getZ();
					$pk->blockId = BlockLegacyIds::AIR;
					$pk->blockMeta = 0;
					$player->putRawPacket($pk);
					return null;
				}

				$clickPos = new Vector3($packet->x, $packet->y, $packet->z);

				$pk = new InventoryTransactionPacket();
				$pk->trData = UseItemTransactionData::new(
					[],
					UseItemTransactionData::ACTION_CLICK_BLOCK,
					BlockPosition::fromVector3($clickPos),
					$packet->face,
					$player->getPlayer()->getInventory()->getHeldItemIndex(),
					$player->getPlayer()->getPosition()->asVector3(),
					$clickPos,
					new BlockPosition(0,0,0),
					RuntimeBlockMapping::getInstance()->toRuntimeId($player->getPlayer()->getWorld()->getBlock($clickPos)->getFullId()));
				return $pk;

			case InboundPacket::USE_ITEM_PACKET:
				if ($player->getPlayer()->getInventory()->getItemInHand()->getId() === ItemIds::WRITTEN_BOOK) {
					$pk = new PluginMessagePacket();
					$pk->channel = "MC|BOpen";
					$pk->data[] = 0;//main hand

					$player->putRawPacket($pk);
					return null;
				}

				$clickPos = new Vector3(0, 0, 0);

				$pk = new InventoryTransactionPacket();
				$pk->trData = UseItemTransactionData::new(
					[],
					UseItemTransactionData::ACTION_CLICK_AIR,
					BlockPosition::fromVector3($clickPos),
					-1,
					$player->getPlayer()->getInventory()->getHeldItemIndex(),
					ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($player->getPlayer()->getInventory()->getItemInHand())),
					$player->getPlayer()->getPosition()->asVector3(),
					$clickPos,
					RuntimeBlockMapping::getInstance()->toRuntimeId($player->getPlayer()->getWorld()->getBlock($clickPos)->getFullId())
				);
				return $pk;
			default:
				//if(DEBUG > 4){
				echo "[Receive][Translator] 0x" . bin2hex(chr($packet->pid())) . " Not implemented\n";
				//}
				return null;
		}
	}

	/**
	 * @param JavaPlayerNetworkSession $player
	 * @param ClientboundPacket $packet
	 * @return Packet|array<Packet>|null
	 * @throws UnexpectedValueException
	 */
	public function serverToInterface(JavaPlayerNetworkSession $player, ClientboundPacket $packet)
	{
		//var_dump("hmm ".$packet->pid());
		echo (new ReflectionClass($packet))->getName() . "\n";
		switch ($packet->pid()) {
			case Info::PLAY_STATUS_PACKET:
				/** @var PlayStatusPacket $packet */
				if ($packet->status === PlayStatusPacket::PLAYER_SPAWN) {
					$pk = new PlayerPositionAndLookPacket();//for loading screen
					$pk->x = $player->getPlayer()->getPosition()->getX();
					$pk->y = $player->getPlayer()->getPosition()->getY();
					$pk->z = $player->getPlayer()->getPosition()->getZ();
					$pk->yaw = 0;
					$pk->pitch = 0;
					$pk->flags = 0;

					return $pk;
				}

				return null;

			case Info::DISCONNECT_PACKET:
				/** @var DisconnectPacket $packet */
				if ($player->status === 0) {
					$pk = new LoginDisconnectPacket();
					$pk->reason = Loader::toJSON($packet->message);
				} else {
					$pk = new PlayDisconnectPacket();
					$pk->reason = Loader::toJSON($packet->message);
				}

				return $pk;

			case Info::TEXT_PACKET:
				/** @var TextPacket $packet */
				if ($packet->message === "chat.type.achievement") {
					$packet->message = "chat.type.advancement.task";
				}

				$pk = new ChatMessagePacket();
				$pk->message = Loader::toJSON($packet->message, $packet->type, $packet->parameters);
				switch ($packet->type) {
					case TextPacket::TYPE_CHAT:
					case TextPacket::TYPE_TRANSLATION:
					case TextPacket::TYPE_WHISPER:
					case TextPacket::TYPE_RAW:
						$pk->position = 0;
						break;
					case TextPacket::TYPE_SYSTEM:
						$pk->position = 1;
						break;
					case TextPacket::TYPE_POPUP:
					case TextPacket::TYPE_TIP:
						$pk->position = 2;
						break;
				}
				$pk->sender = str_repeat("\x00", 16);//Setting both longs to 0 will always display the message regardless of the setting.

				return $pk;

			case Info::SET_TIME_PACKET:
				/** @var SetTimePacket $packet */
				$pk = new TimeUpdatePacket();
				$pk->worldAge = $packet->time;
				$pk->dayTime = $packet->time;
				return $pk;

			case Info::START_GAME_PACKET:
				/** @var StartGamePacket $packet */
				$packets = [];

				$pk = new JoinGamePacket();

				$pk->isHardcore = Server::getInstance()->isHardcore();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->gamemode = $packet->playerGamemode;
				
				$pk->previousGamemode = $packet->playerGamemode;
				$pk->worldNames = ["minecraft:overworld", "minecraft:the_nether", "minecraft:the_end"];
				$pk->dimension = base64_decode("CgAAAQALcGlnbGluX3NhZmUAAQAHbmF0dXJhbAEFAA1hbWJpZW50X2xpZ2h0AAAAAAgACmluZmluaWJ1cm4AHm1pbmVjcmFmdDppbmZpbmlidXJuX292ZXJ3b3JsZAEAFHJlc3Bhd25fYW5jaG9yX3dvcmtzAAEADGhhc19za3lsaWdodAEBAAliZWRfd29ya3MBCAAHZWZmZWN0cwATbWluZWNyYWZ0Om92ZXJ3b3JsZAEACWhhc19yYWlkcwEDAA5sb2dpY2FsX2hlaWdodAAAAQAGABBjb29yZGluYXRlX3NjYWxlP/AAAAAAAAABAAl1bHRyYXdhcm0AAQALaGFzX2NlaWxpbmcAAA==");
				$pk->dimensionCodec = base64_decode("CgAACgAYbWluZWNyYWZ0OmRpbWVuc2lvbl90eXBlCAAEdHlwZQAYbWluZWNyYWZ0OmRpbWVuc2lvbl90eXBlCQAFdmFsdWUKAAAABAgABG5hbWUAE21pbmVjcmFmdDpvdmVyd29ybGQDAAJpZAAAAAAKAAdlbGVtZW50AQALcGlnbGluX3NhZmUAAQAHbmF0dXJhbAEFAA1hbWJpZW50X2xpZ2h0AAAAAAgACmluZmluaWJ1cm4AHm1pbmVjcmFmdDppbmZpbmlidXJuX292ZXJ3b3JsZAEAFHJlc3Bhd25fYW5jaG9yX3dvcmtzAAEADGhhc19za3lsaWdodAEBAAliZWRfd29ya3MBCAAHZWZmZWN0cwATbWluZWNyYWZ0Om92ZXJ3b3JsZAEACWhhc19yYWlkcwEDAA5sb2dpY2FsX2hlaWdodAAAAQAGABBjb29yZGluYXRlX3NjYWxlP/AAAAAAAAABAAl1bHRyYXdhcm0AAQALaGFzX2NlaWxpbmcAAAAIAARuYW1lABltaW5lY3JhZnQ6b3ZlcndvcmxkX2NhdmVzAwACaWQAAAABCgAHZWxlbWVudAEAC3BpZ2xpbl9zYWZlAAEAB25hdHVyYWwBBQANYW1iaWVudF9saWdodAAAAAAIAAppbmZpbmlidXJuAB5taW5lY3JhZnQ6aW5maW5pYnVybl9vdmVyd29ybGQBABRyZXNwYXduX2FuY2hvcl93b3JrcwABAAxoYXNfc2t5bGlnaHQBAQAJYmVkX3dvcmtzAQgAB2VmZmVjdHMAE21pbmVjcmFmdDpvdmVyd29ybGQBAAloYXNfcmFpZHMBAwAObG9naWNhbF9oZWlnaHQAAAEABgAQY29vcmRpbmF0ZV9zY2FsZT/wAAAAAAAAAQAJdWx0cmF3YXJtAAEAC2hhc19jZWlsaW5nAQAACAAEbmFtZQAUbWluZWNyYWZ0OnRoZV9uZXRoZXIDAAJpZAAAAAIKAAdlbGVtZW50AQALcGlnbGluX3NhZmUBAQAHbmF0dXJhbAAFAA1hbWJpZW50X2xpZ2h0PczMzQgACmluZmluaWJ1cm4AG21pbmVjcmFmdDppbmZpbmlidXJuX25ldGhlcgEAFHJlc3Bhd25fYW5jaG9yX3dvcmtzAQEADGhhc19za3lsaWdodAABAAliZWRfd29ya3MACAAHZWZmZWN0cwAUbWluZWNyYWZ0OnRoZV9uZXRoZXIEAApmaXhlZF90aW1lAAAAAAAARlABAAloYXNfcmFpZHMAAwAObG9naWNhbF9oZWlnaHQAAACABgAQY29vcmRpbmF0ZV9zY2FsZUAgAAAAAAAAAQAJdWx0cmF3YXJtAQEAC2hhc19jZWlsaW5nAQAACAAEbmFtZQARbWluZWNyYWZ0OnRoZV9lbmQDAAJpZAAAAAMKAAdlbGVtZW50AQALcGlnbGluX3NhZmUAAQAHbmF0dXJhbAAFAA1hbWJpZW50X2xpZ2h0AAAAAAgACmluZmluaWJ1cm4AGG1pbmVjcmFmdDppbmZpbmlidXJuX2VuZAEAFHJlc3Bhd25fYW5jaG9yX3dvcmtzAAEADGhhc19za3lsaWdodAABAAliZWRfd29ya3MACAAHZWZmZWN0cwARbWluZWNyYWZ0OnRoZV9lbmQEAApmaXhlZF90aW1lAAAAAAAAF3ABAAloYXNfcmFpZHMBAwAObG9naWNhbF9oZWlnaHQAAAEABgAQY29vcmRpbmF0ZV9zY2FsZT/wAAAAAAAAAQAJdWx0cmF3YXJtAAEAC2hhc19jZWlsaW5nAAAAAAoAGG1pbmVjcmFmdDp3b3JsZGdlbi9iaW9tZQgABHR5cGUAGG1pbmVjcmFmdDp3b3JsZGdlbi9iaW9tZQkABXZhbHVlCgAAAE8IAARuYW1lAA9taW5lY3JhZnQ6b2NlYW4DAAJpZAAAAAAKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAe6T/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRov4AAAAUAC3RlbXBlcmF0dXJlPwAAAAUABXNjYWxlPczMzQUACGRvd25mYWxsPwAAAAgACGNhdGVnb3J5AAVvY2VhbgAACAAEbmFtZQAQbWluZWNyYWZ0OnBsYWlucwMAAmlkAAAAAQoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB4p/8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+AAAABQALdGVtcGVyYXR1cmU/TMzNBQAFc2NhbGU9TMzNBQAIZG93bmZhbGw+zMzNCAAIY2F0ZWdvcnkABnBsYWlucwAACAAEbmFtZQAQbWluZWNyYWZ0OmRlc2VydAMAAmlkAAAAAgoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgBusf8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+AAAABQALdGVtcGVyYXR1cmVAAAAABQAFc2NhbGU9TMzNBQAIZG93bmZhbGwAAAAACAAIY2F0ZWdvcnkABmRlc2VydAAACAAEbmFtZQATbWluZWNyYWZ0Om1vdW50YWlucwMAAmlkAAAAAwoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB9ov8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg/gAAABQALdGVtcGVyYXR1cmU+TMzNBQAFc2NhbGU/AAAABQAIZG93bmZhbGw+mZmaCAAIY2F0ZWdvcnkADWV4dHJlbWVfaGlsbHMAAAgABG5hbWUAEG1pbmVjcmFmdDpmb3Jlc3QDAAJpZAAAAAQKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAeab/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPczMzQUAC3RlbXBlcmF0dXJlPzMzMwUABXNjYWxlPkzMzQUACGRvd25mYWxsP0zMzQgACGNhdGVnb3J5AAZmb3Jlc3QAAAgABG5hbWUAD21pbmVjcmFmdDp0YWlnYQMAAmlkAAAABQoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB9o/8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+TMzNBQALdGVtcGVyYXR1cmU+gAAABQAFc2NhbGU+TMzNBQAIZG93bmZhbGw/TMzNCAAIY2F0ZWdvcnkABXRhaWdhAAAIAARuYW1lAA9taW5lY3JhZnQ6c3dhbXADAAJpZAAAAAYKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMIABRncmFzc19jb2xvcl9tb2RpZmllcgAFc3dhbXADAAlza3lfY29sb3IAeKf/AwANZm9saWFnZV9jb2xvcgBqcDkDAA93YXRlcl9mb2dfY29sb3IAIyMXAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAGF7ZAoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGi+TMzNBQALdGVtcGVyYXR1cmU/TMzNBQAFc2NhbGU9zMzNBQAIZG93bmZhbGw/ZmZmCAAIY2F0ZWdvcnkABXN3YW1wAAAIAARuYW1lAA9taW5lY3JhZnQ6cml2ZXIDAAJpZAAAAAcKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAe6T/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRovwAAAAUAC3RlbXBlcmF0dXJlPwAAAAUABXNjYWxlAAAAAAUACGRvd25mYWxsPwAAAAgACGNhdGVnb3J5AAVyaXZlcgAACAAEbmFtZQAXbWluZWNyYWZ0Om5ldGhlcl93YXN0ZXMDAAJpZAAAAAgKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEbm9uZQoAB2VmZmVjdHMKAAVtdXNpYwEAFXJlcGxhY2VfY3VycmVudF9tdXNpYwADAAltYXhfZGVsYXkAAF3ACAAFc291bmQAJG1pbmVjcmFmdDptdXNpYy5uZXRoZXIubmV0aGVyX3dhc3RlcwMACW1pbl9kZWxheQAALuAAAwAJc2t5X2NvbG9yAG6x/wgADWFtYmllbnRfc291bmQAJG1pbmVjcmFmdDphbWJpZW50Lm5ldGhlcl93YXN0ZXMubG9vcAoAD2FkZGl0aW9uc19zb3VuZAgABXNvdW5kACltaW5lY3JhZnQ6YW1iaWVudC5uZXRoZXJfd2FzdGVzLmFkZGl0aW9ucwYAC3RpY2tfY2hhbmNlP4a7mMfigkEAAwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgAzCAgDAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kACRtaW5lY3JhZnQ6YW1iaWVudC5uZXRoZXJfd2FzdGVzLm1vb2QDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg9zMzNBQALdGVtcGVyYXR1cmVAAAAABQAFc2NhbGU+TMzNBQAIZG93bmZhbGwAAAAACAAIY2F0ZWdvcnkABm5ldGhlcgAACAAEbmFtZQARbWluZWNyYWZ0OnRoZV9lbmQDAAJpZAAAAAkKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEbm9uZQoAB2VmZmVjdHMDAAlza3lfY29sb3IAAAAAAwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgCggKADAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPczMzQUAC3RlbXBlcmF0dXJlPwAAAAUABXNjYWxlPkzMzQUACGRvd25mYWxsPwAAAAgACGNhdGVnb3J5AAd0aGVfZW5kAAAIAARuYW1lABZtaW5lY3JhZnQ6ZnJvemVuX29jZWFuAwACaWQAAAAKCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHNub3cKAAdlZmZlY3RzAwAJc2t5X2NvbG9yAH+h/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAOTjJCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aL+AAAAFAAt0ZW1wZXJhdHVyZQAAAAAFAAVzY2FsZT3MzM0FAAhkb3duZmFsbD8AAAAIAAhjYXRlZ29yeQAFb2NlYW4IABR0ZW1wZXJhdHVyZV9tb2RpZmllcgAGZnJvemVuAAAIAARuYW1lABZtaW5lY3JhZnQ6ZnJvemVuX3JpdmVyAwACaWQAAAALCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHNub3cKAAdlZmZlY3RzAwAJc2t5X2NvbG9yAH+h/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAOTjJCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aL8AAAAFAAt0ZW1wZXJhdHVyZQAAAAAFAAVzY2FsZQAAAAAFAAhkb3duZmFsbD8AAAAIAAhjYXRlZ29yeQAFcml2ZXIAAAgABG5hbWUAFm1pbmVjcmFmdDpzbm93eV90dW5kcmEDAAJpZAAAAAwKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEc25vdwoAB2VmZmVjdHMDAAlza3lfY29sb3IAf6H/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPgAAAAUAC3RlbXBlcmF0dXJlAAAAAAUABXNjYWxlPUzMzQUACGRvd25mYWxsPwAAAAgACGNhdGVnb3J5AANpY3kAAAgABG5hbWUAGW1pbmVjcmFmdDpzbm93eV9tb3VudGFpbnMDAAJpZAAAAA0KAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEc25vdwoAB2VmZmVjdHMDAAlza3lfY29sb3IAf6H/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPuZmZgUAC3RlbXBlcmF0dXJlAAAAAAUABXNjYWxlPpmZmgUACGRvd25mYWxsPwAAAAgACGNhdGVnb3J5AANpY3kAAAgABG5hbWUAGW1pbmVjcmFmdDptdXNocm9vbV9maWVsZHMDAAJpZAAAAA4KAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAd6j/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPkzMzQUAC3RlbXBlcmF0dXJlP2ZmZgUABXNjYWxlPpmZmgUACGRvd25mYWxsP4AAAAgACGNhdGVnb3J5AAhtdXNocm9vbQAACAAEbmFtZQAebWluZWNyYWZ0Om11c2hyb29tX2ZpZWxkX3Nob3JlAwACaWQAAAAPCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHeo/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aAAAAAAFAAt0ZW1wZXJhdHVyZT9mZmYFAAVzY2FsZTzMzM0FAAhkb3duZmFsbD+AAAAIAAhjYXRlZ29yeQAIbXVzaHJvb20AAAgABG5hbWUAD21pbmVjcmFmdDpiZWFjaAMAAmlkAAAAEAoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB4p/8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGgAAAAABQALdGVtcGVyYXR1cmU/TMzNBQAFc2NhbGU8zMzNBQAIZG93bmZhbGw+zMzNCAAIY2F0ZWdvcnkABWJlYWNoAAAIAARuYW1lABZtaW5lY3JhZnQ6ZGVzZXJ0X2hpbGxzAwACaWQAAAARCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABG5vbmUKAAdlZmZlY3RzAwAJc2t5X2NvbG9yAG6x/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD7mZmYFAAt0ZW1wZXJhdHVyZUAAAAAFAAVzY2FsZT6ZmZoFAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAGZGVzZXJ0AAAIAARuYW1lABZtaW5lY3JhZnQ6d29vZGVkX2hpbGxzAwACaWQAAAASCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHmm/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD7mZmYFAAt0ZW1wZXJhdHVyZT8zMzMFAAVzY2FsZT6ZmZoFAAhkb3duZmFsbD9MzM0IAAhjYXRlZ29yeQAGZm9yZXN0AAAIAARuYW1lABVtaW5lY3JhZnQ6dGFpZ2FfaGlsbHMDAAJpZAAAABMKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAfaP/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPuZmZgUAC3RlbXBlcmF0dXJlPoAAAAUABXNjYWxlPpmZmgUACGRvd25mYWxsP0zMzQgACGNhdGVnb3J5AAV0YWlnYQAACAAEbmFtZQAXbWluZWNyYWZ0Om1vdW50YWluX2VkZ2UDAAJpZAAAABQKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAfaL/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoP0zMzQUAC3RlbXBlcmF0dXJlPkzMzQUABXNjYWxlPpmZmgUACGRvd25mYWxsPpmZmggACGNhdGVnb3J5AA1leHRyZW1lX2hpbGxzAAAIAARuYW1lABBtaW5lY3JhZnQ6anVuZ2xlAwACaWQAAAAVCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHeo/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD3MzM0FAAt0ZW1wZXJhdHVyZT9zMzMFAAVzY2FsZT5MzM0FAAhkb3duZmFsbD9mZmYIAAhjYXRlZ29yeQAGanVuZ2xlAAAIAARuYW1lABZtaW5lY3JhZnQ6anVuZ2xlX2hpbGxzAwACaWQAAAAWCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHeo/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD7mZmYFAAt0ZW1wZXJhdHVyZT9zMzMFAAVzY2FsZT6ZmZoFAAhkb3duZmFsbD9mZmYIAAhjYXRlZ29yeQAGanVuZ2xlAAAIAARuYW1lABVtaW5lY3JhZnQ6anVuZ2xlX2VkZ2UDAAJpZAAAABcKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAd6j/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPczMzQUAC3RlbXBlcmF0dXJlP3MzMwUABXNjYWxlPkzMzQUACGRvd25mYWxsP0zMzQgACGNhdGVnb3J5AAZqdW5nbGUAAAgABG5hbWUAFG1pbmVjcmFmdDpkZWVwX29jZWFuAwACaWQAAAAYCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHuk/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aL/mZmYFAAt0ZW1wZXJhdHVyZT8AAAAFAAVzY2FsZT3MzM0FAAhkb3duZmFsbD8AAAAIAAhjYXRlZ29yeQAFb2NlYW4AAAgABG5hbWUAFW1pbmVjcmFmdDpzdG9uZV9zaG9yZQMAAmlkAAAAGQoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB9ov8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg9zMzNBQALdGVtcGVyYXR1cmU+TMzNBQAFc2NhbGU/TMzNBQAIZG93bmZhbGw+mZmaCAAIY2F0ZWdvcnkABG5vbmUAAAgABG5hbWUAFW1pbmVjcmFmdDpzbm93eV9iZWFjaAMAAmlkAAAAGgoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARzbm93CgAHZWZmZWN0cwMACXNreV9jb2xvcgB/of8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD1X1goACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGgAAAAABQALdGVtcGVyYXR1cmU9TMzNBQAFc2NhbGU8zMzNBQAIZG93bmZhbGw+mZmaCAAIY2F0ZWdvcnkABWJlYWNoAAAIAARuYW1lABZtaW5lY3JhZnQ6YmlyY2hfZm9yZXN0AwACaWQAAAAbCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHql/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD3MzM0FAAt0ZW1wZXJhdHVyZT8ZmZoFAAVzY2FsZT5MzM0FAAhkb3duZmFsbD8ZmZoIAAhjYXRlZ29yeQAGZm9yZXN0AAAIAARuYW1lABxtaW5lY3JhZnQ6YmlyY2hfZm9yZXN0X2hpbGxzAwACaWQAAAAcCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHql/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD7mZmYFAAt0ZW1wZXJhdHVyZT8ZmZoFAAVzY2FsZT6ZmZoFAAhkb3duZmFsbD8ZmZoIAAhjYXRlZ29yeQAGZm9yZXN0AAAIAARuYW1lABVtaW5lY3JhZnQ6ZGFya19mb3Jlc3QDAAJpZAAAAB0KAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMIABRncmFzc19jb2xvcl9tb2RpZmllcgALZGFya19mb3Jlc3QDAAlza3lfY29sb3IAeab/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPczMzQUAC3RlbXBlcmF0dXJlPzMzMwUABXNjYWxlPkzMzQUACGRvd25mYWxsP0zMzQgACGNhdGVnb3J5AAZmb3Jlc3QAAAgABG5hbWUAFW1pbmVjcmFmdDpzbm93eV90YWlnYQMAAmlkAAAAHgoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARzbm93CgAHZWZmZWN0cwMACXNreV9jb2xvcgCDnv8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD1X1goACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+TMzNBQALdGVtcGVyYXR1cmW/AAAABQAFc2NhbGU+TMzNBQAIZG93bmZhbGw+zMzNCAAIY2F0ZWdvcnkABXRhaWdhAAAIAARuYW1lABttaW5lY3JhZnQ6c25vd3lfdGFpZ2FfaGlsbHMDAAJpZAAAAB8KAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEc25vdwoAB2VmZmVjdHMDAAlza3lfY29sb3IAg57/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA9V9YKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPuZmZgUAC3RlbXBlcmF0dXJlvwAAAAUABXNjYWxlPpmZmgUACGRvd25mYWxsPszMzQgACGNhdGVnb3J5AAV0YWlnYQAACAAEbmFtZQAabWluZWNyYWZ0OmdpYW50X3RyZWVfdGFpZ2EDAAJpZAAAACAKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAfKP/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPkzMzQUAC3RlbXBlcmF0dXJlPpmZmgUABXNjYWxlPkzMzQUACGRvd25mYWxsP0zMzQgACGNhdGVnb3J5AAV0YWlnYQAACAAEbmFtZQAgbWluZWNyYWZ0OmdpYW50X3RyZWVfdGFpZ2FfaGlsbHMDAAJpZAAAACEKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAfKP/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPuZmZgUAC3RlbXBlcmF0dXJlPpmZmgUABXNjYWxlPpmZmgUACGRvd25mYWxsP0zMzQgACGNhdGVnb3J5AAV0YWlnYQAACAAEbmFtZQAabWluZWNyYWZ0Ondvb2RlZF9tb3VudGFpbnMDAAJpZAAAACIKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAfaL/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoP4AAAAUAC3RlbXBlcmF0dXJlPkzMzQUABXNjYWxlPwAAAAUACGRvd25mYWxsPpmZmggACGNhdGVnb3J5AA1leHRyZW1lX2hpbGxzAAAIAARuYW1lABFtaW5lY3JhZnQ6c2F2YW5uYQMAAmlkAAAAIwoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgB1qv8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+AAAABQALdGVtcGVyYXR1cmU/mZmaBQAFc2NhbGU9TMzNBQAIZG93bmZhbGwAAAAACAAIY2F0ZWdvcnkAB3NhdmFubmEAAAgABG5hbWUAGW1pbmVjcmFmdDpzYXZhbm5hX3BsYXRlYXUDAAJpZAAAACQKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEbm9uZQoAB2VmZmVjdHMDAAlza3lfY29sb3IAdqj/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoP8AAAAUAC3RlbXBlcmF0dXJlP4AAAAUABXNjYWxlPMzMzQUACGRvd25mYWxsAAAAAAgACGNhdGVnb3J5AAdzYXZhbm5hAAAIAARuYW1lABJtaW5lY3JhZnQ6YmFkbGFuZHMDAAJpZAAAACUKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEbm9uZQoAB2VmZmVjdHMDAAlza3lfY29sb3IAbrH/AwALZ3Jhc3NfY29sb3IAkIFNAwANZm9saWFnZV9jb2xvcgCegU0DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg9zMzNBQALdGVtcGVyYXR1cmVAAAAABQAFc2NhbGU+TMzNBQAIZG93bmZhbGwAAAAACAAIY2F0ZWdvcnkABG1lc2EAAAgABG5hbWUAIW1pbmVjcmFmdDp3b29kZWRfYmFkbGFuZHNfcGxhdGVhdQMAAmlkAAAAJgoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgBusf8DAAtncmFzc19jb2xvcgCQgU0DAA1mb2xpYWdlX2NvbG9yAJ6BTQMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD/AAAAFAAt0ZW1wZXJhdHVyZUAAAAAFAAVzY2FsZTzMzM0FAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAEbWVzYQAACAAEbmFtZQAabWluZWNyYWZ0OmJhZGxhbmRzX3BsYXRlYXUDAAJpZAAAACcKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEbm9uZQoAB2VmZmVjdHMDAAlza3lfY29sb3IAbrH/AwALZ3Jhc3NfY29sb3IAkIFNAwANZm9saWFnZV9jb2xvcgCegU0DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg/wAAABQALdGVtcGVyYXR1cmVAAAAABQAFc2NhbGU8zMzNBQAIZG93bmZhbGwAAAAACAAIY2F0ZWdvcnkABG1lc2EAAAgABG5hbWUAG21pbmVjcmFmdDpzbWFsbF9lbmRfaXNsYW5kcwMAAmlkAAAAKAoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgAAAAADAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAKCAoAMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg9zMzNBQALdGVtcGVyYXR1cmU/AAAABQAFc2NhbGU+TMzNBQAIZG93bmZhbGw/AAAACAAIY2F0ZWdvcnkAB3RoZV9lbmQAAAgABG5hbWUAFm1pbmVjcmFmdDplbmRfbWlkbGFuZHMDAAJpZAAAACkKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEbm9uZQoAB2VmZmVjdHMDAAlza3lfY29sb3IAAAAAAwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgCggKADAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPczMzQUAC3RlbXBlcmF0dXJlPwAAAAUABXNjYWxlPkzMzQUACGRvd25mYWxsPwAAAAgACGNhdGVnb3J5AAd0aGVfZW5kAAAIAARuYW1lABdtaW5lY3JhZnQ6ZW5kX2hpZ2hsYW5kcwMAAmlkAAAAKgoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgAAAAADAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAKCAoAMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg9zMzNBQALdGVtcGVyYXR1cmU/AAAABQAFc2NhbGU+TMzNBQAIZG93bmZhbGw/AAAACAAIY2F0ZWdvcnkAB3RoZV9lbmQAAAgABG5hbWUAFW1pbmVjcmFmdDplbmRfYmFycmVucwMAAmlkAAAAKwoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgAAAAADAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAKCAoAMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg9zMzNBQALdGVtcGVyYXR1cmU/AAAABQAFc2NhbGU+TMzNBQAIZG93bmZhbGw/AAAACAAIY2F0ZWdvcnkAB3RoZV9lbmQAAAgABG5hbWUAFG1pbmVjcmFmdDp3YXJtX29jZWFuAwACaWQAAAAsCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHuk/wMAD3dhdGVyX2ZvZ19jb2xvcgAEHzMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAQ9XuCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aL+AAAAFAAt0ZW1wZXJhdHVyZT8AAAAFAAVzY2FsZT3MzM0FAAhkb3duZmFsbD8AAAAIAAhjYXRlZ29yeQAFb2NlYW4AAAgABG5hbWUAGG1pbmVjcmFmdDpsdWtld2FybV9vY2VhbgMAAmlkAAAALQoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB7pP8DAA93YXRlcl9mb2dfY29sb3IABBYzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAEWt8goACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGi/gAAABQALdGVtcGVyYXR1cmU/AAAABQAFc2NhbGU9zMzNBQAIZG93bmZhbGw/AAAACAAIY2F0ZWdvcnkABW9jZWFuAAAIAARuYW1lABRtaW5lY3JhZnQ6Y29sZF9vY2VhbgMAAmlkAAAALgoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB7pP8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD1X1goACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGi/gAAABQALdGVtcGVyYXR1cmU/AAAABQAFc2NhbGU9zMzNBQAIZG93bmZhbGw/AAAACAAIY2F0ZWdvcnkABW9jZWFuAAAIAARuYW1lABltaW5lY3JhZnQ6ZGVlcF93YXJtX29jZWFuAwACaWQAAAAvCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHuk/wMAD3dhdGVyX2ZvZ19jb2xvcgAEHzMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAQ9XuCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aL/mZmYFAAt0ZW1wZXJhdHVyZT8AAAAFAAVzY2FsZT3MzM0FAAhkb3duZmFsbD8AAAAIAAhjYXRlZ29yeQAFb2NlYW4AAAgABG5hbWUAHW1pbmVjcmFmdDpkZWVwX2x1a2V3YXJtX29jZWFuAwACaWQAAAAwCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHuk/wMAD3dhdGVyX2ZvZ19jb2xvcgAEFjMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IARa3yCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aL/mZmYFAAt0ZW1wZXJhdHVyZT8AAAAFAAVzY2FsZT3MzM0FAAhkb3duZmFsbD8AAAAIAAhjYXRlZ29yeQAFb2NlYW4AAAgABG5hbWUAGW1pbmVjcmFmdDpkZWVwX2NvbGRfb2NlYW4DAAJpZAAAADEKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAe6T/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA9V9YKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRov+ZmZgUAC3RlbXBlcmF0dXJlPwAAAAUABXNjYWxlPczMzQUACGRvd25mYWxsPwAAAAgACGNhdGVnb3J5AAVvY2VhbgAACAAEbmFtZQAbbWluZWNyYWZ0OmRlZXBfZnJvemVuX29jZWFuAwACaWQAAAAyCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHuk/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAOTjJCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aL/mZmYFAAt0ZW1wZXJhdHVyZT8AAAAFAAVzY2FsZT3MzM0FAAhkb3duZmFsbD8AAAAIAAhjYXRlZ29yeQAFb2NlYW4IABR0ZW1wZXJhdHVyZV9tb2RpZmllcgAGZnJvemVuAAAIAARuYW1lABJtaW5lY3JhZnQ6dGhlX3ZvaWQDAAJpZAAAAH8KAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEbm9uZQoAB2VmZmVjdHMDAAlza3lfY29sb3IAe6T/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPczMzQUAC3RlbXBlcmF0dXJlPwAAAAUABXNjYWxlPkzMzQUACGRvd25mYWxsPwAAAAgACGNhdGVnb3J5AARub25lAAAIAARuYW1lABptaW5lY3JhZnQ6c3VuZmxvd2VyX3BsYWlucwMAAmlkAAAAgQoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB4p/8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+AAAABQALdGVtcGVyYXR1cmU/TMzNBQAFc2NhbGU9TMzNBQAIZG93bmZhbGw+zMzNCAAIY2F0ZWdvcnkABnBsYWlucwAACAAEbmFtZQAWbWluZWNyYWZ0OmRlc2VydF9sYWtlcwMAAmlkAAAAggoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgBusf8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+ZmZmBQALdGVtcGVyYXR1cmVAAAAABQAFc2NhbGU+gAAABQAIZG93bmZhbGwAAAAACAAIY2F0ZWdvcnkABmRlc2VydAAACAAEbmFtZQAcbWluZWNyYWZ0OmdyYXZlbGx5X21vdW50YWlucwMAAmlkAAAAgwoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB9ov8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg/gAAABQALdGVtcGVyYXR1cmU+TMzNBQAFc2NhbGU/AAAABQAIZG93bmZhbGw+mZmaCAAIY2F0ZWdvcnkADWV4dHJlbWVfaGlsbHMAAAgABG5hbWUAF21pbmVjcmFmdDpmbG93ZXJfZm9yZXN0AwACaWQAAACECgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHmm/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD3MzM0FAAt0ZW1wZXJhdHVyZT8zMzMFAAVzY2FsZT7MzM0FAAhkb3duZmFsbD9MzM0IAAhjYXRlZ29yeQAGZm9yZXN0AAAIAARuYW1lABltaW5lY3JhZnQ6dGFpZ2FfbW91bnRhaW5zAwACaWQAAACFCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAH2j/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD6ZmZoFAAt0ZW1wZXJhdHVyZT6AAAAFAAVzY2FsZT7MzM0FAAhkb3duZmFsbD9MzM0IAAhjYXRlZ29yeQAFdGFpZ2EAAAgABG5hbWUAFW1pbmVjcmFmdDpzd2FtcF9oaWxscwMAAmlkAAAAhgoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwgAFGdyYXNzX2NvbG9yX21vZGlmaWVyAAVzd2FtcAMACXNreV9jb2xvcgB4p/8DAA1mb2xpYWdlX2NvbG9yAGpwOQMAD3dhdGVyX2ZvZ19jb2xvcgAjIxcDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAYXtkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aL3MzM0FAAt0ZW1wZXJhdHVyZT9MzM0FAAVzY2FsZT6ZmZoFAAhkb3duZmFsbD9mZmYIAAhjYXRlZ29yeQAFc3dhbXAAAAgABG5hbWUAFG1pbmVjcmFmdDppY2Vfc3Bpa2VzAwACaWQAAACMCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHNub3cKAAdlZmZlY3RzAwAJc2t5X2NvbG9yAH+h/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD7ZmZoFAAt0ZW1wZXJhdHVyZQAAAAAFAAVzY2FsZT7mZmcFAAhkb3duZmFsbD8AAAAIAAhjYXRlZ29yeQADaWN5AAAIAARuYW1lABltaW5lY3JhZnQ6bW9kaWZpZWRfanVuZ2xlAwACaWQAAACVCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHeo/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD5MzM0FAAt0ZW1wZXJhdHVyZT9zMzMFAAVzY2FsZT7MzM0FAAhkb3duZmFsbD9mZmYIAAhjYXRlZ29yeQAGanVuZ2xlAAAIAARuYW1lAB5taW5lY3JhZnQ6bW9kaWZpZWRfanVuZ2xlX2VkZ2UDAAJpZAAAAJcKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAd6j/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPkzMzQUAC3RlbXBlcmF0dXJlP3MzMwUABXNjYWxlPszMzQUACGRvd25mYWxsP0zMzQgACGNhdGVnb3J5AAZqdW5nbGUAAAgABG5hbWUAG21pbmVjcmFmdDp0YWxsX2JpcmNoX2ZvcmVzdAMAAmlkAAAAmwoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB6pf8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+TMzNBQALdGVtcGVyYXR1cmU/GZmaBQAFc2NhbGU+zMzNBQAIZG93bmZhbGw/GZmaCAAIY2F0ZWdvcnkABmZvcmVzdAAACAAEbmFtZQAabWluZWNyYWZ0OnRhbGxfYmlyY2hfaGlsbHMDAAJpZAAAAJwKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAeqX/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPwzMzQUAC3RlbXBlcmF0dXJlPxmZmgUABXNjYWxlPwAAAAUACGRvd25mYWxsPxmZmggACGNhdGVnb3J5AAZmb3Jlc3QAAAgABG5hbWUAG21pbmVjcmFmdDpkYXJrX2ZvcmVzdF9oaWxscwMAAmlkAAAAnQoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwgAFGdyYXNzX2NvbG9yX21vZGlmaWVyAAtkYXJrX2ZvcmVzdAMACXNreV9jb2xvcgB5pv8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+TMzNBQALdGVtcGVyYXR1cmU/MzMzBQAFc2NhbGU+zMzNBQAIZG93bmZhbGw/TMzNCAAIY2F0ZWdvcnkABmZvcmVzdAAACAAEbmFtZQAfbWluZWNyYWZ0OnNub3d5X3RhaWdhX21vdW50YWlucwMAAmlkAAAAngoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARzbm93CgAHZWZmZWN0cwMACXNreV9jb2xvcgCDnv8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD1X1goACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+mZmaBQALdGVtcGVyYXR1cmW/AAAABQAFc2NhbGU+zMzNBQAIZG93bmZhbGw+zMzNCAAIY2F0ZWdvcnkABXRhaWdhAAAIAARuYW1lABxtaW5lY3JhZnQ6Z2lhbnRfc3BydWNlX3RhaWdhAwACaWQAAACgCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAH2j/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD5MzM0FAAt0ZW1wZXJhdHVyZT6AAAAFAAVzY2FsZT5MzM0FAAhkb3duZmFsbD9MzM0IAAhjYXRlZ29yeQAFdGFpZ2EAAAgABG5hbWUAIm1pbmVjcmFmdDpnaWFudF9zcHJ1Y2VfdGFpZ2FfaGlsbHMDAAJpZAAAAKEKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAfaP/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPkzMzQUAC3RlbXBlcmF0dXJlPoAAAAUABXNjYWxlPkzMzQUACGRvd25mYWxsP0zMzQgACGNhdGVnb3J5AAV0YWlnYQAACAAEbmFtZQAlbWluZWNyYWZ0Om1vZGlmaWVkX2dyYXZlbGx5X21vdW50YWlucwMAAmlkAAAAogoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARyYWluCgAHZWZmZWN0cwMACXNreV9jb2xvcgB9ov8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg/gAAABQALdGVtcGVyYXR1cmU+TMzNBQAFc2NhbGU/AAAABQAIZG93bmZhbGw+mZmaCAAIY2F0ZWdvcnkADWV4dHJlbWVfaGlsbHMAAAgABG5hbWUAG21pbmVjcmFmdDpzaGF0dGVyZWRfc2F2YW5uYQMAAmlkAAAAowoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgB2qf8DAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yAMDY/wMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAFm1pbmVjcmFmdDphbWJpZW50LmNhdmUDABNibG9ja19zZWFyY2hfZXh0ZW50AAAACAAABQAFZGVwdGg+uZmaBQALdGVtcGVyYXR1cmU/jMzNBQAFc2NhbGU/nMzNBQAIZG93bmZhbGwAAAAACAAIY2F0ZWdvcnkAB3NhdmFubmEAAAgABG5hbWUAI21pbmVjcmFmdDpzaGF0dGVyZWRfc2F2YW5uYV9wbGF0ZWF1AwACaWQAAACkCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABG5vbmUKAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHao/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD+GZmYFAAt0ZW1wZXJhdHVyZT+AAAAFAAVzY2FsZT+bMzQFAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAHc2F2YW5uYQAACAAEbmFtZQAZbWluZWNyYWZ0OmVyb2RlZF9iYWRsYW5kcwMAAmlkAAAApQoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgBusf8DAAtncmFzc19jb2xvcgCQgU0DAA1mb2xpYWdlX2NvbG9yAJ6BTQMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD3MzM0FAAt0ZW1wZXJhdHVyZUAAAAAFAAVzY2FsZT5MzM0FAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAEbWVzYQAACAAEbmFtZQAqbWluZWNyYWZ0Om1vZGlmaWVkX3dvb2RlZF9iYWRsYW5kc19wbGF0ZWF1AwACaWQAAACmCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABG5vbmUKAAdlZmZlY3RzAwAJc2t5X2NvbG9yAG6x/wMAC2dyYXNzX2NvbG9yAJCBTQMADWZvbGlhZ2VfY29sb3IAnoFNAwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPuZmZgUAC3RlbXBlcmF0dXJlQAAAAAUABXNjYWxlPpmZmgUACGRvd25mYWxsAAAAAAgACGNhdGVnb3J5AARtZXNhAAAIAARuYW1lACNtaW5lY3JhZnQ6bW9kaWZpZWRfYmFkbGFuZHNfcGxhdGVhdQMAAmlkAAAApwoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwMACXNreV9jb2xvcgBusf8DAAtncmFzc19jb2xvcgCQgU0DAA1mb2xpYWdlX2NvbG9yAJ6BTQMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD7mZmYFAAt0ZW1wZXJhdHVyZUAAAAAFAAVzY2FsZT6ZmZoFAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAEbWVzYQAACAAEbmFtZQAXbWluZWNyYWZ0OmJhbWJvb19qdW5nbGUDAAJpZAAAAKgKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEcmFpbgoAB2VmZmVjdHMDAAlza3lfY29sb3IAd6j/AwAPd2F0ZXJfZm9nX2NvbG9yAAUFMwMACWZvZ19jb2xvcgDA2P8DAAt3YXRlcl9jb2xvcgA/duQKAAptb29kX3NvdW5kAwAKdGlja19kZWxheQAAF3AGAAZvZmZzZXRAAAAAAAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAUABWRlcHRoPczMzQUAC3RlbXBlcmF0dXJlP3MzMwUABXNjYWxlPkzMzQUACGRvd25mYWxsP2ZmZggACGNhdGVnb3J5AAZqdW5nbGUAAAgABG5hbWUAHW1pbmVjcmFmdDpiYW1ib29fanVuZ2xlX2hpbGxzAwACaWQAAACpCgAHZWxlbWVudAgADXByZWNpcGl0YXRpb24ABHJhaW4KAAdlZmZlY3RzAwAJc2t5X2NvbG9yAHeo/wMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAwNj/AwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD7mZmYFAAt0ZW1wZXJhdHVyZT9zMzMFAAVzY2FsZT6ZmZoFAAhkb3duZmFsbD9mZmYIAAhjYXRlZ29yeQAGanVuZ2xlAAAIAARuYW1lABptaW5lY3JhZnQ6c291bF9zYW5kX3ZhbGxleQMAAmlkAAAAqgoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwoABW11c2ljAQAVcmVwbGFjZV9jdXJyZW50X211c2ljAAMACW1heF9kZWxheQAAXcAIAAVzb3VuZAAnbWluZWNyYWZ0Om11c2ljLm5ldGhlci5zb3VsX3NhbmRfdmFsbGV5AwAJbWluX2RlbGF5AAAu4AADAAlza3lfY29sb3IAbrH/CAANYW1iaWVudF9zb3VuZAAnbWluZWNyYWZ0OmFtYmllbnQuc291bF9zYW5kX3ZhbGxleS5sb29wCgAPYWRkaXRpb25zX3NvdW5kCAAFc291bmQALG1pbmVjcmFmdDphbWJpZW50LnNvdWxfc2FuZF92YWxsZXkuYWRkaXRpb25zBgALdGlja19jaGFuY2U/hruYx+KCQQAKAAhwYXJ0aWNsZQUAC3Byb2JhYmlsaXR5O8zMzQoAB29wdGlvbnMIAAR0eXBlAA1taW5lY3JhZnQ6YXNoAAADAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yABtHRQMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAJ21pbmVjcmFmdDphbWJpZW50LnNvdWxfc2FuZF92YWxsZXkubW9vZAMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD3MzM0FAAt0ZW1wZXJhdHVyZUAAAAAFAAVzY2FsZT5MzM0FAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAGbmV0aGVyAAAIAARuYW1lABhtaW5lY3JhZnQ6Y3JpbXNvbl9mb3Jlc3QDAAJpZAAAAKsKAAdlbGVtZW50CAANcHJlY2lwaXRhdGlvbgAEbm9uZQoAB2VmZmVjdHMKAAVtdXNpYwEAFXJlcGxhY2VfY3VycmVudF9tdXNpYwADAAltYXhfZGVsYXkAAF3ACAAFc291bmQAJW1pbmVjcmFmdDptdXNpYy5uZXRoZXIuY3JpbXNvbl9mb3Jlc3QDAAltaW5fZGVsYXkAAC7gAAMACXNreV9jb2xvcgBusf8IAA1hbWJpZW50X3NvdW5kACVtaW5lY3JhZnQ6YW1iaWVudC5jcmltc29uX2ZvcmVzdC5sb29wCgAPYWRkaXRpb25zX3NvdW5kCAAFc291bmQAKm1pbmVjcmFmdDphbWJpZW50LmNyaW1zb25fZm9yZXN0LmFkZGl0aW9ucwYAC3RpY2tfY2hhbmNlP4a7mMfigkEACgAIcGFydGljbGUFAAtwcm9iYWJpbGl0eTzMzM0KAAdvcHRpb25zCAAEdHlwZQAXbWluZWNyYWZ0OmNyaW1zb25fc3BvcmUAAAMAD3dhdGVyX2ZvZ19jb2xvcgAFBTMDAAlmb2dfY29sb3IAMwMDAwALd2F0ZXJfY29sb3IAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBgAGb2Zmc2V0QAAAAAAAAAAIAAVzb3VuZAAlbWluZWNyYWZ0OmFtYmllbnQuY3JpbXNvbl9mb3Jlc3QubW9vZAMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD3MzM0FAAt0ZW1wZXJhdHVyZUAAAAAFAAVzY2FsZT5MzM0FAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAGbmV0aGVyAAAIAARuYW1lABdtaW5lY3JhZnQ6d2FycGVkX2ZvcmVzdAMAAmlkAAAArAoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwoABW11c2ljAQAVcmVwbGFjZV9jdXJyZW50X211c2ljAAMACW1heF9kZWxheQAAXcAIAAVzb3VuZAAkbWluZWNyYWZ0Om11c2ljLm5ldGhlci53YXJwZWRfZm9yZXN0AwAJbWluX2RlbGF5AAAu4AADAAlza3lfY29sb3IAbrH/CAANYW1iaWVudF9zb3VuZAAkbWluZWNyYWZ0OmFtYmllbnQud2FycGVkX2ZvcmVzdC5sb29wCgAPYWRkaXRpb25zX3NvdW5kCAAFc291bmQAKW1pbmVjcmFmdDphbWJpZW50LndhcnBlZF9mb3Jlc3QuYWRkaXRpb25zBgALdGlja19jaGFuY2U/hruYx+KCQQAKAAhwYXJ0aWNsZQUAC3Byb2JhYmlsaXR5PGn2qQoAB29wdGlvbnMIAAR0eXBlABZtaW5lY3JhZnQ6d2FycGVkX3Nwb3JlAAADAA93YXRlcl9mb2dfY29sb3IABQUzAwAJZm9nX2NvbG9yABoFGgMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAJG1pbmVjcmFmdDphbWJpZW50LndhcnBlZF9mb3Jlc3QubW9vZAMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD3MzM0FAAt0ZW1wZXJhdHVyZUAAAAAFAAVzY2FsZT5MzM0FAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAGbmV0aGVyAAAIAARuYW1lABdtaW5lY3JhZnQ6YmFzYWx0X2RlbHRhcwMAAmlkAAAArQoAB2VsZW1lbnQIAA1wcmVjaXBpdGF0aW9uAARub25lCgAHZWZmZWN0cwoABW11c2ljAQAVcmVwbGFjZV9jdXJyZW50X211c2ljAAMACW1heF9kZWxheQAAXcAIAAVzb3VuZAAkbWluZWNyYWZ0Om11c2ljLm5ldGhlci5iYXNhbHRfZGVsdGFzAwAJbWluX2RlbGF5AAAu4AADAAlza3lfY29sb3IAbrH/CAANYW1iaWVudF9zb3VuZAAkbWluZWNyYWZ0OmFtYmllbnQuYmFzYWx0X2RlbHRhcy5sb29wCgAPYWRkaXRpb25zX3NvdW5kCAAFc291bmQAKW1pbmVjcmFmdDphbWJpZW50LmJhc2FsdF9kZWx0YXMuYWRkaXRpb25zBgALdGlja19jaGFuY2U/hruYx+KCQQAKAAhwYXJ0aWNsZQUAC3Byb2JhYmlsaXR5PfHa6woAB29wdGlvbnMIAAR0eXBlABNtaW5lY3JhZnQ6d2hpdGVfYXNoAAADAA93YXRlcl9mb2dfY29sb3IAQj5CAwAJZm9nX2NvbG9yAGhfcAMAC3dhdGVyX2NvbG9yAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAYABm9mZnNldEAAAAAAAAAACAAFc291bmQAJG1pbmVjcmFmdDphbWJpZW50LmJhc2FsdF9kZWx0YXMubW9vZAMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAFAAVkZXB0aD3MzM0FAAt0ZW1wZXJhdHVyZUAAAAAFAAVzY2FsZT5MzM0FAAhkb3duZmFsbAAAAAAIAAhjYXRlZ29yeQAGbmV0aGVyAAAAAA==");
				// var_dump(JavaBinarystream::readNBT($pk->dimensionCodec));

				//$player->bigBrother_getDimensionPEToPC($packet->generator);
				$pk->worldName = "minecraft:world";//TODO: dimensionとセットなのでここを更新するときはそれ用のdimension.datを手に入れる必要がある
				$pk->hashedSeed = 0;
				$pk->maxPlayers = Server::getInstance()->getMaxPlayers();
				$pk->viewDistance = 4;//default view Distance is 2 * 2.
				$pk->simulationDistance = 16;//default
				$pk->enableRespawnScreen = true;
				$packets[] = $pk;

				$pk = new PluginMessagePacket();
				$pk->channel = "minecraft:brand";
				$pk->data[] = $packet->serverSoftwareVersion;//display PocketMine Version on F3 Menu
				$packets[] = $pk;

				$pk = new ServerDifficultyPacket();
				$pk->difficulty = $packet->levelSettings->difficulty;
				$packets[] = $pk;

				$pk = new SpawnPositionPacket();
				$pk->x = $packet->levelSettings->spawnPosition->getX();
				$pk->y = $packet->levelSettings->spawnPosition->getY();
				$pk->z = $packet->levelSettings->spawnPosition->getZ();
				$packets[] = $pk;

				$pk = new UpdateViewPositionPacket();
				$pk->chunkX = $player->getPlayer()->getPosition()->getX() >> 4;
				$pk->chunkZ = $player->getPlayer()->getPosition()->getZ() >> 4;
				$packets[] = $pk;

				$pk = new PlayerAbilitiesPacket();
				$pk->flyingSpeed = 0.05;
				$pk->viewModifierField = 0.1;
				$pk->canFly = ($packet->playerGamemode & 0x01) > 0;
				$pk->damageDisabled = ($packet->playerGamemode & 0x01) > 0;
				$pk->isFlying = false;
				$pk->isCreative = ($packet->playerGamemode & 0x01) > 0;
				$packets[] = $pk;

				return $packets;

			case Info::ADD_PLAYER_PACKET:
				/** @var AddPlayerPacket $packet */
				$packets = [];

				$pk = new SpawnPlayerPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->uuid = $packet->uuid->getBytes();
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				// $pk = new EntityMetadataPacket();
				// $pk->entityId = $packet->actorRuntimeId;
				// $pk->metadata = $packet->metadata;
				// $packets[] = $pk;

				$pk = new EntityTeleportPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

				$pk = new EntityEquipmentPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->slot = 0;//main hand
				$pk->item = $packet->item->getItemStack();
				$packets[] = $pk;

				$pk = new EntityHeadLookPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->yaw = $packet->yaw;
				$packets[] = $pk;

				$playerData = null;
				$loggedInPlayers = Server::getInstance()->getOnlinePlayers();
				if (isset($loggedInPlayers[$packet->uuid->getBytes()])) {
					$playerData = $loggedInPlayers[$packet->uuid->getBytes()];
				}

				$skinFlags = 0x7f;//enabled all flags
				if ($playerData->getNetworkSession() instanceof JavaPlayerNetworkSession) {
					if (isset($playerData->bigBrother_getClientSetting()["SkinSettings"])) {
						$skinFlags = $playerData->bigBrother_getClientSetting()["SkinSettings"];
					}
				}

				$pk = new EntityMetadataPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->metadata = [//Enable Display Skin Parts
					16 => [0, $skinFlags],
					"convert" => true,
				];
				$packets[] = $pk;

				$player->addEntityList($packet->actorRuntimeId, "player");
				// if(isset($packet->metadata[Entity::DATA_NAMETAG])){
				// 	$player->bigBrother_setBossBarData("nameTag", $packet->metadata[Entity::DATA_NAMETAG]);
				// }

				return $packets;

			case Info::ADD_ACTOR_PACKET:
				/** @var AddActorPacket $packet */
				return null;
				$packets = [];

				$isObject = false;
				$type = "generic";
				$data = 1;
				$id = 1;

				switch ($packet->type) {
					case EntityLegacyIds::CHICKEN:
						$id = JavaEntityId::CHICKEN;
						break;
					case EntityLegacyIds::COW:
						$id = JavaEntityId::COW;
						break;
					case EntityLegacyIds::PIG:
						$id = JavaEntityId::PIG;
						break;
					case EntityLegacyIds::SHEEP:
						$id = JavaEntityId::SHEEP;
						break;
					case EntityLegacyIds::WOLF:
						$id = JavaEntityId::WOLF;
						break;
					case EntityLegacyIds::VILLAGER:
						$id = JavaEntityId::VILLAGER;
						break;
					case EntityLegacyIds::MOOSHROOM:
						$id = JavaEntityId::MOOSHROOM;
						break;
					case EntityLegacyIds::SQUID:
						$id = JavaEntityId::SQUID;
						break;
					case EntityLegacyIds::RABBIT:
						$id = JavaEntityId::RABBIT;
						break;
					case EntityLegacyIds::BAT:
						$id = JavaEntityId::BAT;
						break;
					case EntityLegacyIds::IRON_GOLEM:
						$id = JavaEntityId::IRON_GOLEM;
						break;
					case EntityLegacyIds::SNOW_GOLEM:
						$id = JavaEntityId::SNOW_GOLEM;
						break;
					case EntityLegacyIds::OCELOT:
						$id = JavaEntityId::OCELOT;
						break;
					case EntityLegacyIds::HORSE:
						$id = JavaEntityId::HORSE;
						break;
					case EntityLegacyIds::DONKEY:
						$id = JavaEntityId::DONKEY;
						break;
					case EntityLegacyIds::MULE:
						$id = JavaEntityId::MULE;
						break;
					case EntityLegacyIds::SKELETON_HORSE:
						$id = JavaEntityId::SKELETON_HORSE;
						break;
					case EntityLegacyIds::ZOMBIE_HORSE:
						$id = JavaEntityId::ZOMBIE_HORSE;
						break;
					case EntityLegacyIds::POLAR_BEAR:
						$id = JavaEntityId::POLAR_BEAR;
						break;
					case EntityLegacyIds::LLAMA:
						$id = JavaEntityId::LLAMA;
						break;
					case EntityLegacyIds::PARROT:
						$id = JavaEntityId::PARROT;
						break;
					case EntityLegacyIds::DOLPHIN:
						$id = JavaEntityId::DOLPHIN;
						break;
					case EntityLegacyIds::ZOMBIE:
						$id = JavaEntityId::ZOMBIE;
						break;
					case EntityLegacyIds::CREEPER:
						$id = JavaEntityId::CREEPER;
						break;
					case EntityLegacyIds::SKELETON:
						$id = JavaEntityId::SKELETON;
						break;
					case EntityLegacyIds::SPIDER:
						$id = JavaEntityId::SPIDER;
						break;
					case EntityLegacyIds::ZOMBIE_PIGMAN:
						$id = JavaEntityId::ZOMBIFIED_PIGLIN;
						break;
					case EntityLegacyIds::SLIME:
						$id = JavaEntityId::SLIME;
						break;
					case EntityLegacyIds::ENDERMAN:
						$id = JavaEntityId::ENDERMAN;
						break;
					case EntityLegacyIds::SILVERFISH:
						$id = JavaEntityId::SILVERFISH;
						break;
					case EntityLegacyIds::CAVE_SPIDER:
						$id = JavaEntityId::CAVE_SPIDER;
						break;
					case EntityLegacyIds::GHAST:
						$id = JavaEntityId::GHAST;
						break;
					case EntityLegacyIds::MAGMA_CUBE:
						$id = JavaEntityId::MAGMA_CUBE;
						break;
					case EntityLegacyIds::BLAZE:
						$id = JavaEntityId::BLAZE;
						break;
					case EntityLegacyIds::ZOMBIE_VILLAGER:
						$id = JavaEntityId::ZOMBIE_VILLAGER;
						break;
					case EntityLegacyIds::WITCH:
						$id = JavaEntityId::WITCH;
						break;
					case EntityLegacyIds::STRAY:
						$id = JavaEntityId::STRAY;
						break;
					case EntityLegacyIds::HUSK:
						$id = JavaEntityId::HUSK;
						break;
					case EntityLegacyIds::WITHER_SKELETON:
						$id = JavaEntityId::WITHER_SKELETON;
						break;
					case EntityLegacyIds::GUARDIAN:
						$id = JavaEntityId::GUARDIAN;
						break;
					case EntityLegacyIds::ELDER_GUARDIAN:
						$id = JavaEntityId::ELDER_GUARDIAN;
						break;
					case EntityLegacyIds::WITHER:
						$id = JavaEntityId::WITHER;
						break;
					case EntityLegacyIds::ENDER_DRAGON:
						$id = JavaEntityId::ENDER_DRAGON;
						break;
					case EntityLegacyIds::SHULKER:
						$id = JavaEntityId::SHULKER;
						break;
					case EntityLegacyIds::ENDERMITE:
						$id = JavaEntityId::ENDERMITE;
						break;
					case EntityLegacyIds::VINDICATOR:
						$id = JavaEntityId::VINDICATOR;
						break;
					case EntityLegacyIds::PHANTOM:
						$id = JavaEntityId::PHANTOM;
						break;
					case EntityLegacyIds::ARMOR_STAND:
						$isObject = true;
						$id = JavaEntityId::ARMOR_STAND;
						break;
					/*case 64://Item
						//Spawn Object
					break;*/
					case EntityLegacyIds::TNT:
						$isObject = true;
						$id = JavaEntityId::TNT;
						break;
					case EntityLegacyIds::FALLING_BLOCK:
						$isObject = true;
						$id = JavaEntityId::FALLING_BLOCK;

						$block = $packet->metadata[2]->getValue();//block data
						$blockId = $block & 0xff;
						$blockDamage = $block >> 8;

						ConvertUtils::convertBlockData(true, $blockId, $blockDamage);

						$data = $blockId | ($blockDamage << 12);
						break;
					case EntityLegacyIds::MOVING_BLOCK:
						//$id = JavaEntityId::;//TODO:working pistion
						break;
					case EntityLegacyIds::XP_BOTTLE:
						$isObject = true;
						$id = JavaEntityId::EXPERIENCE_BOTTLE;
						break;
					case EntityLegacyIds::XP_ORB:
						$entity = $player->getPlayer()->getWorld()->getEntity($packet->actorRuntimeId);

						$pk = new SpawnExperienceOrbPacket();
						$pk->entityId = $packet->actorRuntimeId;
						$pk->x = $packet->position->x;
						$pk->y = $packet->position->y;
						$pk->z = $packet->position->z;
						$pk->count = $entity->getXpValue();

						return $pk;
					case EntityLegacyIds::EYE_OF_ENDER_SIGNAL:
						$isObject = true;
						$id = JavaEntityId::EYE_OF_ENDER;
						break;
					case EntityLegacyIds::ENDER_CRYSTAL:
						$isObject = true;
						$id = JavaEntityId::END_CRYSTAL;
						break;
					case EntityLegacyIds::FIREWORKS_ROCKET:
						$isObject = true;
						$id = JavaEntityId::FIREWORK_ROCKET;
						break;
					case EntityLegacyIds::TRIDENT:
						$isObject = true;
						$id = JavaEntityId::TRIDENT;
						break;
					case EntityLegacyIds::TURTLE:
						$id = JavaEntityId::TURTLE;
						break;
					case EntityLegacyIds::CAT:
						$id = JavaEntityId::CAT;
						break;
					case EntityLegacyIds::SHULKER_BULLET:
						$isObject = true;
						$id = JavaEntityId::SHULKER_BULLET;
						break;
					case EntityLegacyIds::FISHING_HOOK:
						$isObject = true;
						$id = JavaEntityId::FISHING_BOBBER;
						break;
					case EntityLegacyIds::DRAGON_FIREBALL:
						$isObject = true;
						$id = JavaEntityId::DRAGON_FIREBALL;
						break;
					case EntityLegacyIds::ARROW:
						$isObject = true;
						$id = JavaEntityId::ARROW;
						break;
					case EntityLegacyIds::SNOWBALL:
						$isObject = true;
						$id = JavaEntityId::SNOWBALL;
						break;
					case EntityLegacyIds::EGG:
						$isObject = true;
						$id = JavaEntityId::EGG;
						break;
					case EntityLegacyIds::PAINTING:
						$isObject = true;
						$id = JavaEntityId::PAINTING;
						break;
					case EntityLegacyIds::MINECART:
						$isObject = true;
						$id = JavaEntityId::MINECART;
						break;
					case EntityLegacyIds::FIREBALL:
						$isObject = true;
						$id = JavaEntityId::FIREBALL;
						break;
					case EntityLegacyIds::SPLASH_POTION:
						$isObject = true;
						$id = JavaEntityId::POTION;
						break;
					case EntityLegacyIds::ENDER_PEARL:
						$isObject = true;
						$id = JavaEntityId::ENDER_PEARL;
						break;
					case EntityLegacyIds::LEASH_KNOT:
						$isObject = true;
						$id = JavaEntityId::LEASH_KNOT;
						break;
					case EntityLegacyIds::WITHER_SKULL:
						$isObject = true;
						$id = JavaEntityId::WITHER_SKULL;
						break;
					case EntityLegacyIds::BOAT:
						$isObject = true;
						$id = JavaEntityId::BOAT;
						break;
					case EntityLegacyIds::WITHER_SKULL_DANGEROUS:
						$isObject = true;
						$id = JavaEntityId::WITHER_SKULL;
						break;
					case EntityLegacyIds::LIGHTNING_BOLT://Lightning
						$isObject = true;
						$id = JavaEntityId::LIGHTNING_BOLT;
						break;
					case EntityLegacyIds::SMALL_FIREBALL:
						$isObject = true;
						$id = JavaEntityId::SMALL_FIREBALL;
						break;
					case EntityLegacyIds::AREA_EFFECT_CLOUD:
						$isObject = true;
						$id = JavaEntityId::AREA_EFFECT_CLOUD;
						break;
					case EntityLegacyIds::HOPPER_MINECART:
						$isObject = true;
						$id = JavaEntityId::HOPPER_MINECART;
						break;
					case EntityLegacyIds::TNT_MINECART:
						$isObject = true;
						$id = JavaEntityId::TNT_MINECART;
						break;
					case EntityLegacyIds::CHEST_MINECART:
						$isObject = true;
						$id = JavaEntityId::CHEST_MINECART;
						break;
					case EntityLegacyIds::COMMAND_BLOCK_MINECART:
						$isObject = true;
						$id = JavaEntityId::COMMAND_BLOCK_MINECART;
						break;
					case EntityLegacyIds::LINGERING_POTION:
						$isObject = true;
						$id = JavaEntityId::POTION;
						break;
					case EntityLegacyIds::LLAMA_SPIT:
						$isObject = true;
						$id = JavaEntityId::LLAMA_SPIT;
						break;
					case EntityLegacyIds::EVOCATION_FANG:
						$isObject = true;
						$id = JavaEntityId::EVOKER_FANGS;
						break;
					case EntityLegacyIds::EVOCATION_ILLAGER:
						$id = JavaEntityId::EVOKER;
						break;
					case EntityLegacyIds::VEX:
						$id = JavaEntityId::VEX;
						break;
					case EntityLegacyIds::PUFFERFISH:
						$id = JavaEntityId::PUFFERFISH;
						break;
					case EntityLegacyIds::SALMON:
						$id = JavaEntityId::SALMON;
						break;
					case EntityLegacyIds::DROWNED:
						$id = JavaEntityId::DROWNED;
						break;
					case EntityLegacyIds::TROPICAL_FISH:
						$id = JavaEntityId::TROPICAL_FISH;
						break;
					case EntityLegacyIds::COD:
						$id = JavaEntityId::COD;
						break;
					case EntityLegacyIds::PANDA:
						$id = JavaEntityId::PANDA;
						break;
						//TODO:ADD MORE
					default:
						$isObject = true;
						$id = JavaEntityId::ARMOR_STAND;
						echo "AddEntityPacket Eid:" . $packet->actorRuntimeId . "Id: ".$packet->type."\n";
						break;
				}

				if ($isObject) {
					$pk = new SpawnEntityPacket();
					$pk->entityId = $packet->actorRuntimeId;
					$pk->type = $id;
					$pk->uuid = Uuid::uuid4()->getBytes();
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y;
					$pk->z = $packet->position->z;
					$pk->yaw = 0;
					$pk->pitch = 0;
					$pk->data = $data;
					if ($data > 0) {
						$pk->sendVelocity = true;
						$pk->velocityX = 0;
						$pk->velocityY = 0;
						$pk->velocityZ = 0;
					}
					$packets[] = $pk;
				} else {
					$pk = new SpawnLivingEntityPacket();
					$pk->entityId = $packet->actorRuntimeId;
					$pk->type = $id;
					$pk->uuid = Uuid::uuid4()->getBytes();
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y;
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$pk->headPitch = 0;
					$packets[] = $pk;
				}
				$pk = new EntityMetadataPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->metadata = $packet->metadata;
				$packets[] = $pk;

				$pk = new EntityTeleportPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = $packet->yaw;
				$pk->pitch = $packet->pitch;
				$packets[] = $pk;

// 				$player->bigBrother_addEntityList($packet->actorRuntimeId, $type);
// 				if (isset($packet->metadata[EntityMetadataProperties::NAMETAG])) {
// 					$player->bigBrother_setBossBarData("nameTag", $packet->metadata[EntityMetadataProperties::NAMETAG]);
// 				}

				return $packets;

			case Info::REMOVE_ACTOR_PACKET:
				/** @var RemoveActorPacket $packet */
				$packets = [];

				/*if($packet->actorUniqueId === $player->bigBrother_getBossBarData("actorRuntimeId")){
					$uuid = $player->bigBrother_getBossBarData("uuid");
					if($uuid === ""){
						return null;
					}
					$pk = new BossBarPacket();
					$pk->uuid = $uuid;
					$pk->actionId = BossBarPacket::TYPE_REMOVE;

					$player->bigBrother_setBossBarData("actorRuntimeId", -1);
					$player->bigBrother_setBossBarData("uuid", "");

					$packets[] = $pk;
				}*/
				$pk = new DestroyEntitiesPacket();
				$pk->entityIds[] = $packet->actorUniqueId;

				$player->removeEntityList($packet->actorUniqueId);

				$packets[] = $pk;

				return $packets;

			case Info::ADD_ITEM_ACTOR_PACKET:
				/** @var AddItemActorPacket $packet */
				$item = clone $packet->item->getItemStack();
				ConvertUtils::convertItemData(true, $item);
				$metadata = ConvertUtils::convertPEToPCMetadata($packet->metadata);
				$metadata[6] = [7, $item];//6

				$packets = [];

				$pk = new SpawnEntityPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->uuid = Uuid::uuid4()->getBytes();
				$pk->type = SpawnEntityPacket::ITEM_STACK;
				$pk->x = $packet->position->x;
				$pk->y = $packet->position->y;
				$pk->z = $packet->position->z;
				$pk->yaw = 0;
				$pk->pitch = 0;
				$pk->data = 1;
				$pk->sendVelocity = true;
				$pk->velocityX = $packet->motion->x;
				$pk->velocityY = $packet->motion->y;
				$pk->velocityZ = $packet->motion->z;
				$packets[] = $pk;

				$pk = new EntityMetadataPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->metadata = $metadata;
				$packets[] = $pk;

				return $packets;

			case Info::TAKE_ITEM_ACTOR_PACKET:
				/** @var TakeItemActorPacket $packet */
				$pk = $player->getInventoryUtils()->onTakeItemEntity($packet);

				return $pk;

			case Info::MOVE_ACTOR_ABSOLUTE_PACKET:
				/** @var MoveActorAbsolutePacket $packet */
				if ($packet->actorRuntimeId === $player->getPlayer()->getId()) {//TODO
					return null;
				} else {
					$baseOffset = 0;
					$isOnGround = true;
					$entity = $player->getPlayer()->getworld()->getEntity($packet->actorRuntimeId);
					if ($entity instanceof Entity) {
						switch ($entity::getNetworkTypeId()) {
							case "minecraft:player"://Player
								$baseOffset = 1.62;
								break;
							case "minecraft:item"://Item
								$baseOffset = 0.125;
								break;
							case 65://PrimedTNT
							case 66://FallingSand
								$baseOffset = 0.49;
								break;
						}

						$isOnGround = $entity->isOnGround();
					}

					$packets = [];

					$pk = new EntityTeleportPacket();
					$pk->entityId = $packet->actorRuntimeId;
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y - $baseOffset;
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->headYaw;
					$pk->pitch = $packet->yaw;
					$packets[] = $pk;

					$pk = new EntityRotationPacket();
					$pk->entityId = $packet->actorRuntimeId;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$pk->onGround = $isOnGround;
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->entityId = $packet->actorRuntimeId;
					$pk->yaw = $packet->yaw;
					$packets[] = $pk;

					return $packets;
				}

			case Info::MOVE_PLAYER_PACKET:
				/** @var MovePlayerPacket $packet */
				if ($packet->actorRuntimeId === $player->getPlayer()->getId()) {
					if ($player !== null) {
						if ($player->isConnected()) {//for Loading Chunks
							$pk = new PlayerPositionAndLookPacket();//
							$pk->x = $packet->position->x;
							$pk->y = $packet->position->y - $player->getPlayer()->getEyeHeight();
							$pk->z = $packet->position->z;
							$pk->yaw = $packet->yaw;
							$pk->pitch = $packet->pitch;
							$pk->onGround = $packet->onGround;

							return $pk;
						}
					}
				} else {
					$packets = [];

					$pk = new EntityTeleportPacket();
					$pk->entityId = $packet->actorRuntimeId;
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y - $player->getPlayer()->getEyeHeight();
					$pk->z = $packet->position->z;
					$pk->yaw = $packet->yaw;
					$pk->pitch = $packet->pitch;
					$packets[] = $pk;

					$pk = new EntityRotationPacket();
					$pk->entityId = $packet->actorRuntimeId;
					$pk->yaw = $packet->headYaw;
					$pk->pitch = $packet->pitch;
					$pk->onGround = $packet->onGround;
					$packets[] = $pk;

					$pk = new EntityHeadLookPacket();
					$pk->entityId = $packet->actorRuntimeId;
					$pk->yaw = $packet->headYaw;
					$packets[] = $pk;

					return $packets;
				}

				return null;

			case Info::UPDATE_BLOCK_PACKET:
				/** @var UpdateBlockPacket $packet */
				/** @noinspection PhpInternalEntityUsedInspection */
				$b = RuntimeBlockMapping::getInstance()->fromRuntimeId($packet->blockRuntimeId);
				$block = [$b >> 4, $b & 0xf];

				if (($entity = ItemFrameBlockEntity::getItemFrame($player->getPlayer()->getWorld(), $packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ())) !== null) {
					if ($block[0] !== BlockLegacyIds::FRAME_BLOCK) {
						$entity->despawnFrom($player);

						ItemFrameBlockEntity::removeItemFrame($entity);
					} else {
						if (($packet->flags & UpdateBlockPacket::FLAG_NEIGHBORS) == 0) {
							$entity->spawnTo($player);
						}

						return null;
					}
				} else {
					if ($block[0] === BlockLegacyIds::FRAME_BLOCK) {
						$entity = ItemFrameBlockEntity::getItemFrame($player->getPlayer()->getWorld(), $packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ(), $block[1], true);
						$entity->spawnTo($player);

						return null;
					}
				}

				ConvertUtils::convertBlockData(true, $block[0], $block[1]);

				$pk = new BlockChangePacket();
				$pk->x = $packet->blockPosition->getX();
				$pk->y = $packet->blockPosition->getY();
				$pk->z = $packet->blockPosition->getZ();
				$pk->blockId = $block[0];
				$pk->blockMeta = $block[1];
				//TODO: convert block State Id

				return $pk;

			case Info::ADD_PAINTING_PACKET:
				/** @var AddPaintingPacket $packet */
				$spawnPaintingPos = (new Vector3($packet->position->x, $packet->position->y, $packet->position->z))->floor();
				$motives = ["Plant" => 5];

				echo $packet->title . "\n";

				$pk = new SpawnPaintingPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->uuid = Uuid::uuid4()->getBytes();
				$pk->x = $spawnPaintingPos->x;
				$pk->y = $spawnPaintingPos->y;
				$pk->z = $spawnPaintingPos->z;
				$pk->motive = 5;
				$pk->direction = $packet->direction;

				return $pk;

			case Info::CHANGE_DIMENSION_PACKET:
				/** @var ChangeDimensionPacket $packet */
				$pk = new RespawnPacket();
				$pk->dimension = $player->getDimension();
				$pk->worldName = "minecraft:overworld";
				$pk->hashedSeed = 0;
				$pk->gamemode = TypeConverter::getInstance()->coreGameModeToProtocol($player->getPlayer()->getGamemode());
				$pk->previousGamemode = -1;

				$player->respawn();

				return $pk;

			case Info::PLAY_SOUND_PACKET:
				/** @var PlaySoundPacket $packet */
				$pk = new NamedSoundEffectPacket();
				$pk->soundCategory = 0;
				$pk->effectPositionX = (int)$packet->x;
				$pk->effectPositionY = (int)$packet->y;
				$pk->effectPositionZ = (int)$packet->z;
				$pk->volume = $packet->volume * 0.25;
				$pk->pitch = $packet->pitch;
				$pk->soundName = $packet->soundName;

				return $pk;

			case Info::LEVEL_SOUND_EVENT_PACKET:
				/** @var LevelSoundEventPacket $packet */
				$volume = 1;
				$pitch = $packet->extraData;

				switch ($packet->sound) {
					case LevelSoundEvent::EXPLODE:
						$isSoundEffect = true;
						$category = 0;

						$name = "entity.generic.explode";
						break;
					case LevelSoundEvent::CHEST_OPEN:
						$isSoundEffect = true;
						$category = 1;

						$blockId = $player->getPlayer()->getWorld()->getBlock($packet->position)->getId();
						if ($blockId === BlockLegacyIds::ENDER_CHEST) {
							$name = "block.enderchest.open";
						} else {
							$name = "block.chest.open";
						}
						break;
					case LevelSoundEvent::CHEST_CLOSED:
						$isSoundEffect = true;
						$category = 1;

						$blockId = $player->getPlayer()->getWorld()->getBlock($packet->position)->getId();
						if ($blockId === BlockLegacyIds::ENDER_CHEST) {
							$name = "block.enderchest.close";
						} else {
							$name = "block.chest.close";
						}
						break;
					case LevelSoundEvent::NOTE:
						$isSoundEffect = true;
						$category = 2;
						$volume = 3;
						$name = "block.note.harp";//TODO

						$pitch /= 2.0;
						break;
					case LevelSoundEvent::PLACE://unused
						return null;
					default:
						echo "LevelSoundEventPacket: " . $packet->sound . "\n";
						return null;
				}

				if ($isSoundEffect) {
					$pk = new NamedSoundEffectPacket();
					$pk->soundCategory = $category;
					$pk->effectPositionX = (int)$packet->position->x;
					$pk->effectPositionY = (int)$packet->position->y;
					$pk->effectPositionZ = (int)$packet->position->z;
					$pk->volume = $volume;
					$pk->pitch = $pitch;
					$pk->soundName = $name;

					return $pk;
				}

				return null;

			case Info::LEVEL_EVENT_PACKET:
				/** @var LevelEventPacket $packet */
				$isSoundEffect = false;
				$isParticle = false;
				$addData = [];
				$category = 0;
				$name = "";
				$id = 0;

				switch ($packet->eventId) {
					case LevelEvent::PARTICLE_DESTROY;
						return null;
					case LevelEvent::SOUND_IGNITE:
						$isSoundEffect = true;
						$name = "entity.tnt.primed";
						break;
					case LevelEvent::SOUND_SHOOT:
						$isSoundEffect = true;

						switch (($id = $player->getPlayer()->getInventory()->getItemInHand()->getId())) {
							case ItemIds::SNOWBALL:
								$name = "entity.snowball.throw";
								break;
							case ItemIds::EGG:
								$name = "entity.egg.throw";
								break;
							case ItemIds::BOTTLE_O_ENCHANTING:
								$name = "entity.experience_bottle.throw";
								break;
							case ItemIds::SPLASH_POTION:
								$name = "entity.splash_potion.throw";
								break;
							case ItemIds::BOW:
								$name = "entity.arrow.shoot";
								break;
							case 368:
								$name = "entity.enderpearl.throw";
								break;
							default:
								$name = "entity.snowball.throw";

								echo "LevelEventPacket: " . $id . "\n";
								break;
						}
						break;
					case LevelEvent::SOUND_DOOR:
						$isSoundEffect = true;

						$block = $player->getPlayer()->getWorld()->getBlock($packet->position);

						switch ($block->getId()) {
							case BlockLegacyIds::WOODEN_DOOR_BLOCK:
							case BlockLegacyIds::SPRUCE_DOOR_BLOCK:
							case BlockLegacyIds::BIRCH_DOOR_BLOCK:
							case BlockLegacyIds::JUNGLE_DOOR_BLOCK:
							case BlockLegacyIds::ACACIA_DOOR_BLOCK:
							case BlockLegacyIds::DARK_OAK_DOOR_BLOCK:
								if (($block->getMeta() & 0x04) === 0x04) {
									$name = "block.wooden_door.open";
								} else {
									$name = "block.wooden_door.close";
								}
								break;
							case BlockLegacyIds::IRON_DOOR_BLOCK:
								if (($block->getMeta() & 0x04) === 0x04) {
									$name = "block.iron_door.open";
								} else {
									$name = "block.iron_door.close";
								}
								break;
							case BlockLegacyIds::TRAPDOOR:
								if (($block->getMeta() & 0x08) === 0x08) {
									$name = "block.wooden_trapdoor.open";
								} else {
									$name = "block.wooden_trapdoor.close";
								}
								break;
							case BlockLegacyIds::IRON_TRAPDOOR:
								if (($block->getMeta() & 0x08) === 0x08) {
									$name = "block.iron_trapdoor.open";
								} else {
									$name = "block.iron_trapdoor.close";
								}
								break;
							case BlockLegacyIds::OAK_FENCE_GATE:
							case BlockLegacyIds::SPRUCE_FENCE_GATE:
							case BlockLegacyIds::BIRCH_FENCE_GATE:
							case BlockLegacyIds::JUNGLE_FENCE_GATE:
							case BlockLegacyIds::DARK_OAK_FENCE_GATE:
							case BlockLegacyIds::ACACIA_FENCE_GATE:
								if (($block->getMeta() & 0x04) === 0x04) {
									$name = "block.fence_gate.open";
								} else {
									$name = "block.fence_gate.close";
								}
								break;
							default:
								echo "[LevelEventPacket] Unknown DoorSound\n";
								return null;
						}
						break;
					case LevelEvent::ADD_PARTICLE_MASK | ParticleIds::CRITICAL:
						$isParticle = true;
						$id = 9;
						break;
					case LevelEvent::ADD_PARTICLE_MASK | ParticleIds::HUGE_EXPLODE_SEED:
						$isParticle = true;
						$id = 2;
						break;
					case LevelEvent::ADD_PARTICLE_MASK | ParticleIds::TERRAIN:
						$isParticle = true;

						/** @noinspection PhpInternalEntityUsedInspection */
						$b = RuntimeBlockMapping::getInstance()->fromRuntimeId($packet->data);//block data
						$block = [$b >> 4, $b & 0xf];
						ConvertUtils::convertBlockData(true, $block[0], $block[1]);

						$packet->data = $block[0] | ($block[1] << 12);

						$id = 37;
						$addData = [
							$packet->eventData
						];
						break;
					case LevelEvent::ADD_PARTICLE_MASK | ParticleIds::DUST:
						$isParticle = true;
						$id = 46;
						$addData = [
							$packet->eventData //TODO: RGBA
						];
						break;
					/*case LevelEvent::ADD_PARTICLE_MASK | ParticleIds::INK:
					break;*/
					case LevelEvent::ADD_PARTICLE_MASK | ParticleIds::SNOWBALL_POOF:
						$isParticle = true;
						$id = 31;
						break;
					case LevelEvent::ADD_PARTICLE_MASK | ParticleIds::ITEM_BREAK:
						//TODO
						break;
					/*case LevelEvent::PARTICLE_DESTROY:
						/** @noinspection PhpInternalEntityUsedInspection *
						$b = RuntimeBlockMapping::getInstance()->fromRuntimeId($packet->data);//block data
						$block = [$b >> 4, $b & 0xf];
						ConvertUtils::convertBlockData(true, $block[0], $block[1]);

						$packet->data = $block[0] | ($block[1] << 12);
					break;*/
					case LevelEvent::PARTICLE_PUNCH_BLOCK:
						//TODO: BreakAnimation
						return null;
					case LevelEvent::BLOCK_START_BREAK:
						//TODO: set BreakTime
						return null;
					case LevelEvent::BLOCK_STOP_BREAK:
						//TODO: remove BreakTime

						return null;
					default:
						if (($packet->eventId & LevelEvent::ADD_PARTICLE_MASK) === LevelEvent::ADD_PARTICLE_MASK) {
							$packet->eventId ^= LevelEvent::ADD_PARTICLE_MASK;
						}

						echo "LevelEventPacket: " . $packet->eventId . "\n";
						return null;
				}

				if ($isSoundEffect) {
					$pk = new NamedSoundEffectPacket();
					$pk->soundCategory = $category;
					$pk->effectPositionX = (int)$packet->position->x;
					$pk->effectPositionY = (int)$packet->position->y;
					$pk->effectPositionZ = (int)$packet->position->z;
					$pk->volume = 0.5;
					$pk->pitch = 1.0;
					$pk->soundName = $name;
				} elseif ($isParticle) {
					$pk = new ParticlePacket();
					$pk->particleId = $id;
					$pk->longDistance = false;
					$pk->x = $packet->position->x;
					$pk->y = $packet->position->y;
					$pk->z = $packet->position->z;
					$pk->offsetX = 0;
					$pk->offsetY = 0;
					$pk->offsetZ = 0;
					$pk->particleData = $packet->data;
					$pk->particleCount = 1;
					$pk->data = $addData;//!!!!!!!!!!!!!!!!!!!!!!!!!!!
				} else {
					$pk = new EffectPacket();
					$pk->effectId = $packet->eventId;
					$pk->x = (int)$packet->position->x;
					$pk->y = (int)$packet->position->y;
					$pk->z = (int)$packet->position->z;
					$pk->data = $packet->data;
					$pk->disableRelativeVolume = false;
				}

				return $pk;

			case Info::BLOCK_EVENT_PACKET:
				/** @var BlockEventPacket $packet */
				$pk = new BlockActionPacket();
				$pk->x = $packet->x;
				$pk->y = $packet->y;
				$pk->z = $packet->z;
				$pk->actionId = $packet->eventType;
				$pk->actionParam = $packet->eventData;
				$pk->blockType = $player->getPlayer()->getWorld()->getBlock(new Vector3($packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ()))->getId();

				return $pk;

			case Info::SET_TITLE_PACKET:
				/** @var SetTitlePacket $packet */
				switch ($packet->type) {
					case SetTitlePacket::TYPE_CLEAR_TITLE:
						$pk = new ClearTitlesPacket();
						$pk->resetTimes = false;

						return $pk;
					case SetTitlePacket::TYPE_RESET_TITLE:
						$pk = new ClearTitlesPacket();
						$pk->resetTimes = true;

						return $pk;
					case SetTitlePacket::TYPE_SET_TITLE_JSON:
					case SetTitlePacket::TYPE_SET_TITLE:
						$pk = new SetTitleTextPacket();
						$pk->text = ($packet->type == SetTitlePacket::TYPE_SET_TITLE) ? Loader::toJSON($packet->text) : $packet->text;

						return $pk;
					case SetTitlePacket::TYPE_SET_SUBTITLE_JSON:
					case SetTitlePacket::TYPE_SET_SUBTITLE:
						$pk = new SetSubtitleTextPacket();
						$pk->text = ($packet->type == SetTitlePacket::TYPE_SET_SUBTITLE) ? Loader::toJSON($packet->text) : $packet->text;

						return $pk;
					case SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE_JSON:
					case SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE:
						$pk = new SetActionBarTextPacket();
						$pk->text = ($packet->type == SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE) ? Loader::toJSON($packet->text) : $packet->text;

						return $pk;
					case SetTitlePacket::TYPE_SET_ANIMATION_TIMES:
						$pk = new SetTitlesAnimationPacket();
						$pk->fadeIn = $packet->fadeInTime;
						$pk->stay = $packet->stayTime;
						$pk->fadeOut = $packet->fadeOutTime;

						return $pk;
				}

				return null;

			case Info::ACTOR_EVENT_PACKET:
				/** @var ActorEventPacket $packet */
				switch ($packet->eventId) {
					case ActorEvent::HURT_ANIMATION:
						$type = $player->bigBrother_getEntityList($packet->actorRuntimeId);

						$packets = [];

						$pk = new EntityStatusPacket();
						$pk->entityStatus = 2;
						$pk->entityId = $packet->actorRuntimeId;
						$packets[] = $pk;

						$pk = new NamedSoundEffectPacket();
						$pk->soundCategory = 0;
						$pk->effectPositionX = (int)$player->getPlayer()->getPosition()->getX();
						$pk->effectPositionY = (int)$player->getPlayer()->getPosition()->getY();
						$pk->effectPositionZ = (int)$player->getPlayer()->getPosition()->getZ();
						$pk->volume = 0.5;
						$pk->pitch = 1.0;
						$pk->soundName = "entity." . $type . ".hurt";
						$packets[] = $pk;

						return $packets;
					case ActorEvent::DEATH_ANIMATION:
						$type = $player->bigBrother_getEntityList($packet->actorRuntimeId);

						$packets = [];

						$pk = new EntityStatusPacket();
						$pk->entityStatus = 3;
						$pk->entityId = $packet->actorRuntimeId;
						$packets[] = $pk;

						$pk = new NamedSoundEffectPacket();
						$pk->soundCategory = 0;
						$pk->effectPositionX = (int)$player->getPlayer()->getPosition()->getX();
						$pk->effectPositionY = (int)$player->getPlayer()->getPosition()->getY();
						$pk->effectPositionZ = (int)$player->getPlayer()->getPosition()->getZ();
						$pk->volume = 0.5;
						$pk->pitch = 1.0;
						$pk->soundName = "entity." . $type . ".death";
						$packets[] = $pk;

						return $packets;
					case ActorEvent::RESPAWN:
						//unused
						break;
					default:
						echo "EntityEventPacket: " . $packet->event . "\n";
						break;
				}

				return null;

			case Info::MOB_EFFECT_PACKET:
				/** @var MobEffectPacket $packet */
				switch ($packet->eventId) {
					case MobEffectPacket::EVENT_ADD:
					case MobEffectPacket::EVENT_MODIFY:
						$flags = 0;
						if ($packet->particles) {
							$flags |= 0x02;
						}
						$flags |= 0x04;//Show icon

						$pk = new EntityEffectPacket();
						$pk->entityId = $packet->actorRuntimeId;
						$id = $packet->effectId;
						if($id == 24 || $id == 27){
							$id++;
						}elseif($id == 25){
							$id = 19;
						}elseif($id == 26 || $id >= 28){
							$id += 3;
						}
						$pk->effectId = $id;
						$pk->amplifier = $packet->amplifier;
						$pk->duration = $packet->duration;
						$pk->flags = $flags;

						return $pk;
					case MobEffectPacket::EVENT_REMOVE:
						$pk = new RemoveEntityEffectPacket();
						$pk->entityId = $packet->actorRuntimeId;
						$id = $packet->effectId;
						if($id == 24 || $id == 27){
							$id++;
						}elseif($id == 25){
							$id = 19;
						}elseif($id == 26 || $id >= 28){
							$id += 3;
						}
						$pk->effectId = $id;
						return $pk;
				}

				return null;

			// case Info::UPDATE_ATTRIBUTES_PACKET:
			// 	/** @var UpdateAttributesPacket $packet */
			// 	$packets = [];
			// 	$entries = [];

			// 	foreach($packet->entries as $entry){
			// 		switch($entry->getId()){
			// 			case "minecraft:player.saturation": //TODO
			// 			case "minecraft:player.exhaustion": //TODO
			// 			case "minecraft:absorption": //TODO
			// 			break;
			// 			case "minecraft:player.hunger": //move to minecraft:health
			// 			break;
			// 			case "minecraft:health":
			// 				if($packet->actorRuntimeId === $player->getPlayer()->getId()){
			// 					$pk = new UpdateHealthPacket();
			// 					$pk->health = $player->getPlayer()->getHealth();//TODO: Default Value
			// 					$pk->food = (int) $player->getPlayer()->getHungerManager()->getFood();//TODO: Default Value
			// 					$pk->foodSaturation = $player->getPlayer()->getHungerManager()->getSaturation();//TODO: Default Value
			// 				}else{
			// 					$pk = new EntityMetadataPacket();
			// 					$pk->entityId = $packet->actorRuntimeId;
			// 					$pk->metadata = [
			// 						8 => [2, $entry->getCurrent()],
			// 						"convert" => true,
			// 					];
			// 				}

			// 				$packets[] = $pk;
			// 			break;
			// 			case "minecraft:movement":
			// 				$entries[] = [
			// 					"generic.movement_speed",
			// 					$entry->getCurrent()//TODO: Default Value
			// 				];
			// 			break;
			// 			case "minecraft:player.level": //move to minecraft:player.experience
			// 			break;
			// 			case "minecraft:player.experience":
			// 				if($packet->actorRuntimeId === $player->getPlayer()->getId()){
			// 					$pk = new SetExperiencePacket();
			// 					$pk->experienceBar = $entry->getCurrent();//TODO: Default Value
			// 					$pk->level = $player->getPlayer()->getXpManager()->getXpLevel();//TODO: Default Value
			// 					$pk->totalExperience = $player->getPlayer()->getXpManager()->getLifetimeTotalXp();//TODO: Default Value

			// 					$packets[] = $pk;
			// 				}
			// 			break;
			// 			case "minecraft:attack_damage":
			// 				$entries[] = [
			// 					"generic.attack_damage",
			// 					$entry->getCurrent()//TODO: Default Value
			// 				];
			// 			break;
			// 			case "minecraft:knockback_resistance":
			// 				$entries[] = [
			// 					"generic.knockback_resistance",
			// 					$entry->getCurrent()//TODO: Default Value
			// 				];
			// 			break;
			// 			case "minecraft:follow_range":
			// 				$entries[] = [
			// 					"generic.follow_range",
			// 					$entry->getCurrent()//TODO: Default Value
			// 				];
			// 			break;
			// 			default:
			// 				echo "UpdateAtteributesPacket: ".$entry->getId()."\n";
			// 			break;
			// 		}
			// 	}

			// 	if(count($entries) > 0){
			// 		$pk = new EntityPropertiesPacket();
			// 		$pk->entityId = $packet->actorRuntimeId;
			// 		$pk->entries = $entries;
			// 		$packets[] = $pk;
			// 	}

			// 	return $packets;

			case Info::MOB_EQUIPMENT_PACKET:
				/** @var MobEquipmentPacket $packet */
				$packets = [];

				if ($packet->actorRuntimeId === $player->getPlayer()->getId()) {
					$pk = new HeldItemChangePacket();
					$pk->slot = $packet->hotbarSlot;
					$packets[] = $pk;
				}

				$pk = new EntityEquipmentPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->slot = 0;//main hand
				$pk->item = $packet->item->getItemStack();

				if (count($packets) > 0) {
					$packets[] = $pk;

					return $packets;
				}

				return $pk;

			// case Info::MOB_ARMOR_EQUIPMENT_PACKET:
			// 	/** @var MobArmorEquipmentPacket $packet */
			// 	return $player->getInventoryUtils()->onMobArmorEquipment($packet);

			case Info::SET_ACTOR_DATA_PACKET:
				/** @var SetActorDataPacket $packet */
				$packets = [];

				/*foreach($packet->metadata as $key => $d){
					if($d->getTypeId() == EntityMetadataProperties::PLAYER_BED_POSITION){
						$bedXYZ = $key;
						if($bedXYZ !== null){
						/** @var Vector3 $bedXYZ */
						/*$pk = new UseBedPacket();
						$pk->entityId = $packet->actorRuntimeId;
						$pk->bedX = $bedXYZ->getX();
						$pk->bedY = $bedXYZ->getY();
						$pk->bedZ = $bedXYZ->getZ();

						$packets[] = $pk;*/
						/*}
					}
				}*/

				// $pk = new EntityMetadataPacket();
				// $pk->entityId = $packet->actorRuntimeId;
				// $pk->metadata = $packet->metadata;
				// $packets[] = $pk;

				return $packets;

			case Info::SET_ACTOR_MOTION_PACKET:
				/** @var SetActorMotionPacket $packet */
				$pk = new EntityVelocityPacket();
				$pk->entityId = $packet->actorRuntimeId;
				$pk->velocityX = $packet->motion->x;
				$pk->velocityY = $packet->motion->y;
				$pk->velocityZ = $packet->motion->z;
				return $pk;

			case Info::SET_HEALTH_PACKET:
				/** @var SetHealthPacket $packet */
				$pk = new UpdateHealthPacket();
				$pk->health = $packet->health;//TODO: Default Value
				$pk->food = (int)$player->getPlayer()->getHungerManager()->getFood();//TODO: Default Value
				$pk->foodSaturation = $player->getPlayer()->getHungerManager()->getSaturation();//TODO: Default Value
				return $pk;

			case Info::SET_SPAWN_POSITION_PACKET:
				/** @var SetSpawnPositionPacket $packet */
				$pk = new SpawnPositionPacket();
				$pk->x = $packet->spawnPosition->getX();
				$pk->y = $packet->spawnPosition->getY();
				$pk->z = $packet->spawnPosition->getZ();
				return $pk;

			case Info::ANIMATE_PACKET:
				/** @var AnimatePacket $packet */
				switch ($packet->action) {
					case 1:
						$pk = new STCAnimatePacket();
						$pk->animation = 0;
						$pk->entityId = $packet->actorRuntimeId;
						return $pk;
					case 3: //Leave Bed
						$pk = new STCAnimatePacket();
						$pk->animation = 2;
						$pk->entityId = $packet->actorRuntimeId;
						return $pk;
					default:
						echo "AnimationPacket: " . $packet->action . "\n";
						break;
				}
				return null;

			// case Info::CONTAINER_OPEN_PACKET:
			// 	/** @var ContainerOpenPacket $packet */
			// 	return $player->getInventoryUtils()->onWindowOpen($packet);

			// case Info::CONTAINER_CLOSE_PACKET:
			// 	/** @var ContainerClosePacket $packet */
			// 	return $player->getInventoryUtils()->onWindowCloseFromPEtoPC($packet);

			// case Info::INVENTORY_SLOT_PACKET:
			// 	/** @var InventorySlotPacket $packet */
			// 	return $player->getInventoryUtils()->onWindowSetSlot($packet);

			// case Info::CONTAINER_SET_DATA_PACKET:
			// 	/** @var ContainerSetDataPacket $packet */
			// 	return $player->getInventoryUtils()->onWindowSetData($packet);

			// case Info::CRAFTING_DATA_PACKET:
			// 	/** @var CraftingDataPacket $packet */
			// 	return $player->getRecipeUtils()->onCraftingData($packet);

			// case Info::INVENTORY_CONTENT_PACKET:
			// 	/** @var InventoryContentPacket $packet */
			// 	return $player->getInventoryUtils()->onWindowSetContent($packet);

			case Info::BLOCK_ACTOR_DATA_PACKET:
				/** @var BlockActorDataPacket $packet */
				$pk = new BlockEntityDataPacket();
				$pk->x = $packet->blockPosition->getX();
				$pk->y = $packet->blockPosition->getY();
				$pk->z = $packet->blockPosition->getZ();

				/*$nbt = new NetworkLittleEndianNBTStream();
				$nbt = $nbt->read($packet->namedtag, true);

				switch($nbt["id"]){
					case JavaTile::BANNER:
						$pk->actionId = 6;
						$pk->nbtData = $nbt;
					break;
					case JavaTile::BED:
						$pk->actionId = 11;
						$pk->nbtData = $nbt;
					break;
					case JavaTile::CHEST:
					case JavaTile::ENCHANT_TABLE:
					case JavaTile::ENDER_CHEST:
					case JavaTile::FURNACE:
						$pk->actionId = 7;
						$pk->nbtData = $nbt;
					break;
					case JavaTile::FLOWER_POT:
						$pk->actionId = 5;
						/** @var CompoundTag $nbt */
						/*$pk->nbtData = ConvertUtils::convertBlockEntity(true, $nbt);
					break;
					case JavaTile::ITEM_FRAME:
						if(($entity = ItemFrameBlockEntity::getItemFrame($player->getPlayer()->getWorld(), $packet->x, $packet->y, $packet->z)) !== null){
							$entity->spawnTo($player);//Update Item Frame
						}
						return null;
					case JavaTile::SIGN:
						$pk->actionId = 9;
						/** @var CompoundTag $nbt */
						/*$pk->nbtData = ConvertUtils::convertBlockEntity(true, $nbt);
					break;
					case JavaTile::SKULL:
						$pk->actionId = 4;
						$pk->nbtData = $nbt;
					break;
					default:
						echo "BlockEntityDataPacket: ".$nbt["id"]."\n";
						return null;
				}*/

				return null;

			case Info::SET_DIFFICULTY_PACKET:
				/** @var SetDifficultyPacket $packet */
				$pk = new ServerDifficultyPacket();
				$pk->difficulty = $packet->difficulty;
				return $pk;

			case Info::SET_PLAYER_GAME_TYPE_PACKET:
				/** @var SetPlayerGameTypePacket $packet */
				$packets = [];

				$pk = new PlayerAbilitiesPacket();
				$pk->flyingSpeed = 0.05;
				$pk->viewModifierField = 0.1;
				$pk->canFly = ($packet->gamemode & 0x01) > 0;
				$pk->damageDisabled = ($packet->gamemode & 0x01) > 0;
				$pk->isFlying = false;
				$pk->isCreative = ($packet->gamemode & 0x01) > 0;
				$packets[] = $pk;

				$pk = new ChangeGameStatePacket();
				$pk->reason = 3;
				$pk->value = $packet->gamemode;
				$packets[] = $pk;

				return $packets;

			// case Info::LEVEL_CHUNK_PACKET:
			// 	/** @var LevelChunkPacket $packet */
			// 	$task = new chunktask($packet->getChunkX(), $packet->getChunkZ(), $player->getPlayer()->getWorld()->getChunk($packet->getChunkX(), $packet->getChunkZ()), $player);
			// 	Server::getInstance()->getAsyncPool()->submitTask($task);
			// 	var_dump("Level chunk");
			// 	return null;

			case Info::PLAYER_LIST_PACKET:
				/** @var PlayerListPacket $packet */
				$pk = new PlayerInfoPacket();

				switch ($packet->type) {
					case 0://Add
						$pk->actionId = PlayerInfoPacket::TYPE_ADD;

						$loggedInPlayers = Server::getInstance()->getOnlinePlayers();
						foreach ($packet->entries as $entry) {
							$playerData = null;
							$gameMode = 0;
							$displayName = $entry->username;
							if (isset($loggedInPlayers[$entry->uuid->getBytes()])) {
								$playerData = $loggedInPlayers[$entry->uuid->getBytes()];
								$gameMode = TypeConverter::getInstance()->coreGameModeToProtocol($playerData->getGamemode());
								$displayName = $playerData->getNameTag();
							}
							$ns = $playerData?->getNetworkSession();
							if ($ns instanceof JavaPlayerNetworkSession) {
								$properties = $ns?->bigBrother_getProperties();
							} else {
								//TODO: Skin Problem
								$value = [//Dummy Data
									"timestamp" => 0,
									"profileId" => str_replace("-", "", $entry->uuid->toString()),
									"profileName" => TextFormat::clean($entry->username),
									"textures" => [
										"SKIN" => [
											//TODO
										]
									]
								];

								$properties = [
									[
										"name" => "textures",
										"value" => base64_encode(json_encode($value)),
									]
								];
							}

							$pk->players[] = [
								$entry->uuid->getBytes(),
								substr(TextFormat::clean($displayName), 0, 16),
								$properties,
								$gameMode,
								0,
								true,
								Loader::toJSON($entry->username)
							];
						}
						break;
					case 1://Remove
						$pk->actionId = PlayerInfoPacket::TYPE_REMOVE;

						foreach ($packet->entries as $entry) {
							$pk->players[] = [
								$entry->uuid->getBytes(),
							];
						}
						break;
				}

				return $pk;

			case Info::CLIENTBOUND_MAP_ITEM_DATA_PACKET:
				/** @var ClientboundMapItemDataPacket $packet */
				$pk = new MapPacket();

				$pk->mapId = $packet->mapId;
				$pk->scale = $packet->scale;
				$pk->columns = $packet->width;
				$pk->rows = $packet->height;

				// TODO implement tracked entities handling and general map behaviour

				$pk->data = ColorUtils::convertColorsToPC($packet->colors, $packet->xOffset, $packet->yOffset);

				return $pk;

			case Info:: CHUNK_RADIUS_UPDATED_PACKET:
				/** @var ChunkRadiusUpdatedPacket $packet */
				$pk = new UpdateViewDistancePacket();
				$pk->viewDistance = $packet->radius * 2;
				return $pk;

			case Info::BOSS_EVENT_PACKET:
				/** @var BossEventPacket $packet */
				$pk = new BossBarPacket();
				$uuid = $player->bigBrother_getBossBarData("uuid");

				switch ($packet->eventType) {
					case BossEventPacket::TYPE_REGISTER_PLAYER:
					case BossEventPacket::TYPE_UNREGISTER_PLAYER:
					case BossEventPacket::TYPE_UNKNOWN_6:
						break;
					case BossEventPacket::TYPE_SHOW:
						if ($uuid !== "") {
							return null;
						}
						$pk->uuid = Uuid::uuid4()->getBytes();
						$pk->actionId = BossBarPacket::TYPE_ADD;
						if (isset($packet->title) and is_string($packet->title) and strlen($packet->title) > 0) {
							$title = $packet->title;
						} else {
							$title = $player->bigBrother_getBossBarData("nameTag")[1];
						}
						$pk->title = Loader::toJSON(str_replace(["\r\n", "\r", "\n"], "", $title));
						$health = 1.0;
						if ($packet->healthPercent < 100) { //healthPercent is a value between 1 and 100
							$health = $packet->healthPercent / 100;
						} elseif ($packet->healthPercent <= 0) {
							$health = 0.0;
						}
						$pk->health = $health;

						$player->bigBrother_setBossBarData("entityRuntimeId", $packet->bossEid);
						$player->bigBrother_setBossBarData("uuid", $pk->uuid);

						return $pk;
					case BossEventPacket::TYPE_HIDE:
						if ($uuid === "") {
							return null;
						}
						$pk->uuid = $uuid;
						$pk->actionId = BossBarPacket::TYPE_REMOVE;

						$player->bigBrother_setBossBarData("entityRuntimeId", -1);
						$player->bigBrother_setBossBarData("uuid", "");

						return $pk;
					case BossEventPacket::TYPE_TEXTURE:
						if ($uuid === "") {
							return null;
						}
						$pk->uuid = $uuid;
						$pk->actionId = BossBarPacket::TYPE_UPDATE_COLOR;
						$pk->color = $packet->color;

						return $pk;
					case BossEventPacket::TYPE_HEALTH_PERCENT:
						if ($uuid === "") {
							return null;
						}
						$pk->uuid = $uuid;
						$pk->actionId = BossBarPacket::TYPE_UPDATE_HEALTH;
						$health = 1.0;
						if ($packet->healthPercent < 100) { //healthPercent is a value between 1 and 100
							$health = $packet->healthPercent / 100;
						} elseif ($packet->healthPercent <= 0) {
							$health = 0.0;
						}
						$pk->health = $health;

						return $pk;
					case BossEventPacket::TYPE_TITLE:
						if ($uuid === "") {
							return null;
						}
						$pk->uuid = $uuid;
						$pk->actionId = BossBarPacket::TYPE_UPDATE_TITLE;
						$pk->title = Loader::toJSON(str_replace(["\r\n", "\r", "\n"], "", $packet->title));

						return $pk;
					default:
						echo "BossEventPacket: " . $packet->eventType . "\n";
						break;
				}
				return null;
			case Info::SET_DISPLAY_OBJECTIVE_PACKET:
				/** @var SetDisplayObjectivePacket $packet */

				$packets = [];

				$pk = new ScoreboardObjectivePacket();
				$pk->action = ScoreboardObjectivePacket::ACTION_ADD;
				$pk->displayName = $packet->displayName;
				$pk->type = ScoreboardObjectivePacket::TYPE_INTEGER;
				$pk->name = $packet->objectiveName;
				$packets[] = $pk;

				$pk = new DisplayScoreboardPacket();
				$pk->position = DisplayScoreboardPacket::POSITION_SIDEBAR;
				$pk->name = $packet->objectiveName;
				$packets[] = $pk;

				return $packets;
			case Info::SET_SCORE_PACKET:
				/** @var SetScorePacket $packet */
				$packets = [];
				$i = 16;
				foreach ($packet->entries as $entry) {
					$i--;
					$pk = new UpdateScorePacket();
					$pk->action = UpdateScorePacket::ACTION_ADD_OR_UPDATE;
					$pk->value = $i;
					$pk->objective = $entry->objectiveName;
					$pk->entry = $entry->customName;
					$packets[] = $pk;
				}
				return $packets;
			case Info::REMOVE_OBJECTIVE_PACKET:
				/** @var RemoveObjectivePacket $packet */
				$pk = new ScoreboardObjectivePacket();
				$pk->action = ScoreboardObjectivePacket::ACTION_REMOVE;
				$pk->name = $packet->objectiveName;
				return $pk;
			/*case Info::MODAL_FORM_REQUEST_PACKET: check if he is in then send it
				/** @var ModalFormRequestPacket $packet *
				$formData = json_decode($packet->formData, true);
				$packets = [];
				if ($formData["type"] === "form") {
					$pk = new ChatPacket();
					$pk->message = json_encode(["text" => TextFormat::BOLD . TextFormat::GRAY . "============ [> " . TextFormat::RESET . $formData["title"] . TextFormat::RESET . " <] ============\n" . TextFormat::RESET . $formData["content"] . TextFormat::RESET . "\n\n"]);
					$packets[] = $pk;
					foreach ($formData["buttons"] as $i => $a) {
						$pk = new ChatPacket();
						$pk->message = json_encode(["text" => TextFormat::BOLD . TextFormat::GOLD . "[CLICK #" . $i . "] " . TextFormat::RESET . $a["text"], "clickEvent" => ["action" => "run_command", "value" => ")respondform " . $i]]);
						$packets[] = $pk;
					}
					$pk = new ChatPacket();
					$pk->message = json_encode(["text" => TextFormat::BOLD . TextFormat::GOLD . "[CLOSE] ", "clickEvent" => ["action" => "run_command", "value" => ")respondform ESC"]]);
					$packets[] = $pk;
				}
				$player->bigBrother_formId = $packet->formId;
				return $packets;*/

			case Info::UPDATE_ABILITIES_PACKET:
				/** @var UpdateAbilitiesPacket $packet */
				$data = $packet->getData()->getAbilityLayers()[0];
				$BoolAbilities = $data->getBoolAbilities();
				$isFlying = $BoolAbilities[AbilitiesLayer::ABILITY_FLYING];
				$canFly = $BoolAbilities[AbilitiesLayer::ABILITY_ALLOW_FLIGHT];
				$damageDisabled = $BoolAbilities[AbilitiesLayer::ABILITY_INVULNERABLE];
				$pk = new PlayerAbilitiesPacket();
				$pk->flyingSpeed = $data->getFlySpeed();
				$pk->viewModifierField = 0.1;
				$pk->canFly = $canFly;
				$pk->damageDisabled = $damageDisabled;
				$pk->isFlying = $isFlying;
				$pk->isCreative = (TypeConverter::getInstance()->coreGameModeToProtocol($player->getPlayer()->getGamemode()) & 0x01) > 0;
				return $pk;

			case Info::RESOURCE_PACKS_INFO_PACKET:
			case Info::RESPAWN_PACKET:
			case Info::AVAILABLE_COMMANDS_PACKET:
			case Info::AVAILABLE_ACTOR_IDENTIFIERS_PACKET:
			case Info::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET:
			case Info::BIOME_DEFINITION_LIST_PACKET:
			case Info::CREATIVE_CONTENT_PACKET:
				return null;

			default:
// 				echo "[Send][Translator] 0x" . bin2hex(chr($packet->pid())) . " Not implemented\n";
				return null;
		}
	}
}
